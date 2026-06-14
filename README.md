# Scheme Monitor

A single-container **Laravel 12 + Livewire** portal for monitoring government /
welfare schemes, with **dynamic role-based access control**, a **configurable logo**,
and a **secret-URL first-run onboarding** wizard. Until setup is finished the public
sees an *“under construction”* page.

Built to run on one **FrankenPHP** container, sized for a **GCS `e2-micro` VM
(2 vCPU / 1 GB RAM)**.

---

## Features

1. **User management with dynamic roles & permissions** — create roles and
   permissions at runtime (no redeploy), assign them to users. Backed by
   [spatie/laravel-permission]. A `super-admin` role implicitly holds everything.
2. **Scheme Monitoring dashboard** — KPIs (budget allocated/disbursed, utilisation,
   beneficiary coverage) and charts (status, category, budget) over welfare schemes,
   plus full scheme CRUD.
3. **Configurable branding + onboarding** — a one-time wizard at a secret URL
   (`/setup/{secret}`) sets the portal name & logo and creates the first
   super-administrator. Before completion every public route shows the construction page.
4. **URL shortener with click tracking** — create root-level short links (`/{code}`,
   custom or auto-generated), enable/disable them, and track total + unique clicks per link.
5. **Home-page visitor analytics** — every hit on `/` is counted and split into
   **unique vs repeat visitors** (privacy-respecting hashed cookie + hashed IP),
   surfaced on the dashboard.

## Stack at a glance

| Layer        | Choice                                            |
|--------------|---------------------------------------------------|
| Runtime      | **FrankenPHP** (Caddy + embedded PHP 8.3, auto-HTTPS), one container |
| Framework    | **Laravel 12**, **Livewire 3** (TALL stack)       |
| RBAC         | **spatie/laravel-permission 6**                   |
| Database     | **SQLite** (WAL) — zero extra memory              |
| Frontend     | **Tailwind 3** + **Vite**, **Chart.js** (CDN)     |
| Cache/session| File-based (no Redis needed)                      |
| Container    | Multi-stage build, ~1 image, healthcheck on `/up` |
| Host target  | GCS `e2-micro` (free tier), 2 GB swap             |

See [docs/USER_GUIDE.md](docs/USER_GUIDE.md) for the full architecture & operations guide.

---

## Quick start — local development (Docker only)

> No PHP, Composer, or Node needed on your machine — everything runs in containers.

```bash
docker compose -f compose.dev.yaml up --build
# or: make dev
```

- App:  <http://localhost:8000>
- Vite HMR: <http://localhost:5173>
- The dev stack auto-creates `.env`, generates a key, migrates, and seeds demo schemes.
- Onboarding wizard (dev secret = `dev-setup-secret`):
  <http://localhost:8000/setup/dev-setup-secret>

Common tasks (all run inside Docker):

```bash
make artisan c="migrate:status"
make composer c="require vendor/package"
make npm c="run build"
make tinker
make test
```

## Quick start — production on a VM (SSH + GHCR)

Bring your own VM (e2-micro or anything you can SSH into). The image is built &
pushed to **GitHub Container Registry** from your machine and **pulled** on the VM
over plain SSH — no provider CLI, and the VM never builds.

```bash
cp deploy/config.env.example deploy/config.env   # set SSH_HOST/SSH_USER, IMAGE_OWNER, optional DOMAIN
export GHCR_TOKEN=ghp_xxx                         # GitHub PAT with write:packages
# for a private package also: export GHCR_PULL_TOKEN=$GHCR_TOKEN

make configure   # one-time: install Docker + 2 GB swap on the VM (over SSH)
make release     # build → push to GHCR → pull & run on the VM
```

Iterate later with `make release` (or `make push` / `make deploy` individually). The
deploy step prints your one-time setup URL, e.g. `http://<SSH_HOST>/setup/<secret>`.
Open it, create the super-admin, and the portal goes live. Full walkthrough:
[docs/USER_GUIDE.md](docs/USER_GUIDE.md).

## Project layout

```
app/Livewire/            Dashboard, Auth, Onboarding, Users, Roles, Permissions, Schemes
app/Http/Middleware/     EnsureOnboarded (the under-construction gate)
app/Models/              User, Scheme, Setting
config/onboarding.php    Secret + default onboarding checklist
database/                Migrations, seeders (PermissionSeeder, DemoSchemeSeeder)
docker/                  Caddyfile, php tuning, entrypoints
Dockerfile               Multi-stage FrankenPHP image
docker-compose.yml       Production single-container stack
compose.dev.yaml         Docker-only dev stack (app + vite)
deploy/                  SSH deploy scripts (configure [one-time], build-push→GHCR, deploy)
docs/USER_GUIDE.md       Full setup & operations guide
```

[spatie/laravel-permission]: https://spatie.be/docs/laravel-permission
