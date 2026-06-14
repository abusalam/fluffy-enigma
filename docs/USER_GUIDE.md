# Scheme Monitor — Setup & Operations Guide

This guide covers the full stack, local development, deploying to a Google Cloud
`e2-micro` VM, and day-2 operations.

- [1. Architecture & stack](#1-architecture--stack)
- [2. How the app behaves (onboarding, RBAC, dashboard)](#2-how-the-app-behaves)
- [3. Local development (Docker only)](#3-local-development-docker-only)
- [4. Production deploy to a GCS e2-micro VM](#4-production-deploy-to-a-gcs-e2-micro-vm)
- [5. Environment variables](#5-environment-variables)
- [6. Operations (backup, logs, updates, TLS)](#6-operations)
- [7. Troubleshooting](#7-troubleshooting)
- [8. Security notes](#8-security-notes)

---

## 1. Architecture & stack

Everything runs in **one container**. FrankenPHP (a single Go binary that embeds
the Caddy web server and a PHP runtime) serves the Laravel application directly —
there is no separate Nginx/PHP-FPM/supervisor to manage, which keeps memory usage
low enough for a 1 GB VM.

```
                Internet
                   │  :80 / :443 (+ :443/udp HTTP/3)
        ┌──────────▼───────────────────────────────┐
        │  Docker container  (scheme-monitor)       │
        │                                           │
        │   FrankenPHP  ── Caddy ──► php_server      │
        │        │            (auto-HTTPS if DOMAIN) │
        │        ▼                                   │
        │   Laravel 12  +  Livewire 3 (TALL)         │
        │        │                                   │
        │   SQLite (WAL)   file cache   file sessions│
        └──────────────┬────────────────────────────┘
                       │
        volume: app-storage  (SQLite DB + uploads)  +  caddy-data (certs)
```

| Component | Version / choice | Why |
|-----------|------------------|-----|
| Web/runtime | **FrankenPHP 1.x**, PHP 8.3 | One process, auto-HTTPS, low RAM, HTTP/3 |
| Framework | **Laravel 12** | LTS-grade, batteries included |
| UI | **Livewire 3** + Tailwind 3 + Alpine (bundled) | Server-rendered, no SPA build at runtime |
| Charts | **Chart.js 4** (CDN) | No bundler weight |
| RBAC | **spatie/laravel-permission 6** | Battle-tested dynamic roles/permissions |
| DB | **SQLite** with WAL journ=normal | Zero extra memory; ideal for a single small VM |
| Cache | File store | No Redis to run |
| Sessions | File driver | Same |
| Queue | `sync` | No worker process needed for this workload |

**Memory budget (typical idle):** FrankenPHP + PHP + OPcache ≈ 120–200 MB, leaving
plenty of the 1 GB for the OS page cache and request spikes. A 2 GB swap file
absorbs the Docker image **build** (the only memory-hungry step) and rare bursts.

**Why SQLite here?** A single container on one VM has one writer; SQLite in WAL mode
comfortably handles a monitoring/admin workload with far less RAM and operational
overhead than MySQL/Postgres. The DB lives on the `app-storage` Docker volume
(`/app/storage/app/database/database.sqlite`). If you
later outgrow it, `config/database.php` already includes a MySQL connection — point
`DB_*` at Cloud SQL and run migrations.

---

## 2. How the app behaves

### Onboarding & the “under construction” gate

- The `EnsureOnboarded` middleware (registered globally on the `web` group) checks a
  `settings` flag `onboarding_completed`.
- **Until it is true**, every public route returns the **under-construction** page —
  *except* the secret setup URL, Livewire’s update endpoint, and static assets.
- The setup wizard lives at **`/setup/{secret}`** where `{secret}` must equal
  `APP_ONBOARDING_SECRET` (config `onboarding.secret`). A wrong/empty secret → `404`.
- The wizard (4 steps): **Welcome → Branding (name + logo) → Super administrator →
  Review/Finish**. On finish it:
  1. ensures baseline roles/permissions exist,
  2. stores the portal name + logo (logo to the `public` disk),
  3. creates the first user and assigns the **`super-admin`** role,
  4. sets `onboarding_completed = true`, logs you in, and the portal goes live.
- After completion the secret URL simply redirects to the login page.

> Rotate the secret any time by changing `APP_ONBOARDING_SECRET` — it only matters
> during first-run.

### Dynamic roles & permissions

- Permissions are atomic strings used in route + UI guards, e.g.
  `schemes.view`, `schemes.manage`, `users.manage`, `roles.manage`, `permissions.manage`,
  `dashboard.view`.
- **Roles** and **permissions** are fully editable at runtime via the UI
  (Users / Roles / Permissions pages) — no redeploy required.
- Starter roles seeded by `PermissionSeeder` (idempotent, runs on every boot):
  - `super-admin` — implicitly all access (via `Gate::before`); protected from edit/delete.
  - `administrator` — every explicit permission.
  - `scheme-manager` — dashboard + scheme view/manage.
  - `viewer` — dashboard + scheme view.
- Guardrails: you can’t delete your own account, the last super-admin, the
  `super-admin` role, or a role still assigned to users.

### URL shortener & visitor analytics

- **Short links** are managed at `/links` (permissions `shortlinks.view` /
  `shortlinks.manage`). Each has a `code`, destination URL, optional title, and an
  active toggle. Codes can be custom or auto-generated; reserved app paths
  (`login`, `dashboard`, …) are rejected.
- **Per-user scoping**: each link belongs to its creator. Regular users see and
  manage **only their own** links. Holders of `shortlinks.view_all` —
  **administrator** (and **super-admin** via `Gate::before`) — see and manage **all**
  links, with an extra *Owner* column. Edit/delete/toggle are guarded server-side
  (a non-owner without `view_all` gets a 403), and the dashboard link stats are
  scoped the same way.
- The public redirect is a **root-level `/{code}`** → 302 to the destination (404 if
  missing or disabled). It's registered as the last route, so every named app route
  (login, dashboard, setup, assets, …) takes precedence; the code charset constraint
  excludes paths with dots/slashes, and reserved words can't be used as codes. Every
  redirect increments `clicks`, and `unique_clicks` when it's the first time a given
  visitor opens that link.
- **Home-page hits**: every request to `/` is logged (`home_visits`) and the visitor
  registry (`visitors`) is updated. The dashboard shows **total hits, unique
  visitors, returning visitors (>1 visit), and repeat hits**, plus today's count.
- **How unique/repeat is determined**: a first-party `vid` cookie (1-year, UUID)
  identifies a browser; a per-request **SHA-256 hash of IP + APP_KEY** is stored
  instead of the raw IP. No third-party tracking, no PII at rest. Logic lives in
  `app/Support/VisitTracker.php`.
- These hit-log tables grow with traffic; for a high-traffic site, periodically prune
  `home_visits` / `short_link_clicks` (the denormalised counters on `short_links` and
  `visitors` are kept regardless).

### Scheme model (government / welfare)

Each **Scheme** has: name, unique code, department, category (Health, Education,
Agriculture, Housing, Employment, Pension, Nutrition, Financial Inclusion), status
(draft / active / suspended / closed), start/end dates, budget allocated & disbursed,
target & enrolled beneficiaries, description. The dashboard derives **budget
utilisation %** and **beneficiary coverage %** from these.

---

## 3. Local development (Docker only)

**Prerequisite:** Docker Desktop / Engine with the Compose plugin. Nothing else.

```bash
make dev          # = docker compose -f compose.dev.yaml up --build
```

What it does on first run: installs Composer deps into a volume, copies `.env`,
generates `APP_KEY`, migrates, seeds the baseline roles **and demo schemes**, and
starts FrankenPHP (port 8000) + a Vite dev server (port 5173) with hot reload.

| URL | Purpose |
|-----|---------|
| <http://localhost:8000> | App (under-construction until you onboard) |
| <http://localhost:8000/setup/dev-setup-secret> | Onboarding wizard |
| <http://localhost:5173> | Vite dev server (assets/HMR) |

Useful commands (all execute **inside** the containers):

```bash
make shell                       # sh into the app container
make artisan c="migrate:fresh --seed"
make composer c="require barryvdh/laravel-debugbar --dev"
make npm c="run build"
make tinker
make test
make dev-down                    # stop & remove
```

OPcache timestamp validation is **on** in dev (via an injected php.ini), so PHP edits
appear immediately; Blade/Livewire/JS hot-reload through Vite.

---

## 4. Production deploy to a VM (SSH + GHCR)

You provide the VM (an e2-micro, any cloud, or bare metal). The image is built and
pushed to **GitHub Container Registry (GHCR)** from your machine; the VM **pulls** it
over plain **SSH** — no provider CLI. The VM never builds (the build is the only
memory-hungry step), so deploys are fast and reliable on 1 GB RAM.

### Prerequisites

- A VM you can **SSH into**, with a user that has **sudo** (to run Docker). Debian/
  Ubuntu is assumed by the bootstrap (apt). Open inbound **tcp/22, 80, 443** (and
  udp/443 for HTTP/3) in your provider's firewall.
- **Docker** locally (to build & push the image).
- A **GitHub Personal Access Token (classic)** with `write:packages` (and
  `read:packages`). Export it: `export GHCR_TOKEN=ghp_xxx`.

### Step 1 — configure

```bash
cp deploy/config.env.example deploy/config.env
$EDITOR deploy/config.env
```

Set `SSH_HOST`, `SSH_USER` (and `SSH_PORT`/`SSH_KEY` if non-default), and
`IMAGE_OWNER` (your lowercase GitHub user/org). Optionally set `DOMAIN` (a hostname
pointed at the VM) for automatic HTTPS; leave it blank to serve plain HTTP on
`SSH_HOST`.

### Step 2 — one-time VM configuration

```bash
make configure        # = ./deploy/configure.sh
```

Over SSH this installs **Docker Engine + Compose**, creates a **2 GB swap file**
(`SWAP_SIZE`), and caps Docker log size. Idempotent — safe to re-run. Run it once per
VM (or after a rebuild).

### Step 3 — build, push & deploy

```bash
export GHCR_TOKEN=ghp_xxx          # write:packages
# private package? also: export GHCR_PULL_TOKEN=$GHCR_TOKEN
make release                       # = make push + make deploy
```

- **`deploy/build-push.sh`** (`make push`) — builds the image locally for
  `linux/amd64` and pushes `ghcr.io/<owner>/scheme-monitor:<timestamp>` **and**
  `:latest`. The timestamp tag is recorded in `deploy/.last-image`.
- **`deploy/deploy.sh`** (`make deploy`) — over SSH copies `docker-compose.yml`,
  writes a production `.env` (fresh `APP_KEY` + `APP_ONBOARDING_SECRET`,
  `SERVER_NAME`/`APP_URL` from `DOMAIN`/`SSH_HOST`, and `APP_IMAGE`= the pushed tag),
  then runs `docker compose pull && docker compose up -d --no-build`.

To ship a change later: `make release`. To roll back, pass an older tag:
`./deploy/deploy.sh ghcr.io/<owner>/scheme-monitor:<older-timestamp>`.

> **GHCR package visibility:** new packages are **private**. Either make the package
> public on GitHub (then the VM pulls anonymously), or export `GHCR_PULL_TOKEN`
> before deploying so the VM authenticates with `docker login`.

At the end it prints:

```
Public site (under construction until setup): http://<SSH_HOST>/
One-time setup wizard: http://<SSH_HOST>/setup/<generated-secret>
```

### Step 4 — onboard

Open the setup URL, complete the 4-step wizard, and the portal is live. The
under-construction page disappears for everyone.

### Using a domain + HTTPS

1. Create a DNS **A record** for your hostname → the VM’s external IP.
2. Put that hostname in `deploy/config.env` as `DOMAIN="scheme.example.com"`.
3. Re-run `make deploy`. Caddy obtains and renews a Let’s Encrypt certificate
   automatically (ports 80+443 must be reachable). Certs persist in the
   `caddy-data` volume.

### Re-deploying changes

```bash
./deploy/deploy.sh        # re-tar, upload, rebuild, restart (keeps .env, DB, certs)
```

---

## 5. Environment variables

Set in `.env` (production) or the dev compose file. Key ones:

| Variable | Default | Notes |
|----------|---------|-------|
| `APP_NAME` | `Scheme Monitor` | Overridden by the portal name set during onboarding |
| `APP_KEY` | — | **Required.** Generated by the deploy script / entrypoint |
| `APP_ENV` | `production` | `local` in dev |
| `APP_DEBUG` | `false` | Keep false in prod |
| `APP_URL` | `http://localhost` | Use `https://<domain>` when serving TLS |
| `APP_ONBOARDING_SECRET` | — | The `{secret}` in `/setup/{secret}`. Long & random |
| `SERVER_NAME` | `:80` | `:80` = HTTP; a hostname = Caddy auto-HTTPS |
| `DB_CONNECTION` | `sqlite` | `DB_DATABASE` points at `/app/database/database.sqlite` |
| `SESSION_DRIVER` | `file` | |
| `CACHE_STORE` | `file` | |
| `SEED_DEMO_DATA` | `false` | `true` loads demo schemes on boot (idempotent) |
| `LOG_STACK` | `stderr` | Logs go to container stdout/stderr |

> **`APP_KEY` persistence:** in production the key lives in the VM’s `.env` (created
> once by the deploy script) and is baked into the cached config at container start.
> Don’t regenerate it after data exists, or encrypted values/sessions break.

---

## 6. Operations

SSH into the VM (`ssh <SSH_USER>@<SSH_HOST>`) and run these from inside
`REMOTE_DIR` (default `/opt/scheme-monitor`).

**Tail logs** (from your laptop, over SSH):
```bash
ssh <SSH_USER>@<SSH_HOST> 'cd /opt/scheme-monitor && sudo docker compose logs -f --tail=100 app'
```

**Restart / stop / start:**
```bash
sudo docker compose restart app
sudo docker compose down
sudo docker compose up -d
```

**Run artisan in prod** (e.g. create a user manually):
```bash
sudo docker compose exec app php artisan tinker
```

**Back up the database** (SQLite is a single file on the `app-storage` volume):
```bash
# on the VM, in REMOTE_DIR
sudo docker compose cp app:/app/storage/app/database/database.sqlite ./backup-$(date +%F).sqlite
```
Copy it off the VM with `scp <SSH_USER>@<SSH_HOST>:/opt/scheme-monitor/backup-*.sqlite .`.
Uploaded logos live in the same `app-storage` volume
(`/app/storage/app/public/branding`).

**Update the app:** edit code locally, then `make release` (build → push to GHCR →
pull on the VM). The VM never builds; it just pulls the new image. Migrations run
automatically at container start. Roll back with
`./deploy/deploy.sh ghcr.io/<owner>/scheme-monitor:<older-timestamp>`.

**Resource check:**
```bash
sudo docker stats --no-stream
free -h
```

**Stop / tear down the app** (the VM itself you manage in your provider):
```bash
ssh <SSH_USER>@<SSH_HOST> 'cd /opt/scheme-monitor && sudo docker compose down'
# add -v to also delete the data volumes (DB, uploads, certs) — irreversible
```

---

## 7. Troubleshooting

| Symptom | Likely cause / fix |
|---------|--------------------|
| Build killed / OOM | Builds run on your machine now, not the VM. If building locally on low RAM, ensure swap; the VM only pulls. |
| `docker pull` denied on the VM | Private GHCR package — `export GHCR_PULL_TOKEN=$GHCR_TOKEN` and re-run `./deploy/deploy.sh`, or make the package public. |
| `/setup/...` returns 404 | `APP_ONBOARDING_SECRET` empty or mismatched; check `.env`, then `docker compose up -d` |
| Still see “under construction” after onboarding | `onboarding_completed` not set — check logs; config cache stale → `docker compose restart app` |
| HTTPS not issued | DNS A record not pointing at the IP yet, or ports 80/443 blocked; Caddy retries — see logs |
| 500 on first request | Usually a missing `APP_KEY`; ensure it’s set in `.env`, then restart |
| Assets 404 in prod | The image build runs `npm run build`; rebuild + redeploy with `make release` |
| Permission denied on storage | Entrypoint chowns on boot; if a volume was pre-created, `docker compose restart app` |

View detailed logs over SSH: `ssh <SSH_USER>@<SSH_HOST> 'cd /opt/scheme-monitor && sudo docker compose logs -f app'`.
Temporarily set `APP_DEBUG=true` in `.env` + restart to see stack traces — revert after.

---

## 8. Security notes

- **Secret URL** is an obscurity layer for *first-run only*; it’s disabled after
  onboarding. Still keep it long/random and avoid logging it.
- **HTTPS:** use a `DOMAIN` in production so credentials aren’t sent over plain HTTP.
- **SSH** is exposed only to Google’s IAP range, not the public internet.
- **`super-admin`** bypasses all permission checks — grant sparingly.
- Login is **rate-limited** (5 attempts per email+IP) and disabled accounts
  (`is_active = false`) cannot authenticate.
- Keep `APP_DEBUG=false` in production.
- Back up the SQLite volume regularly (see §6).
