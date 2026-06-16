# Fluffy Enigma

[![CI](https://github.com/abusalam/fluffy-enigma/actions/workflows/ci.yml/badge.svg)](https://github.com/abusalam/fluffy-enigma/actions/workflows/ci.yml)

A single-container **Laravel 12 + Livewire** portal with **dynamic role-based access
control**, a **URL shortener** with click tracking, **home-page visitor analytics**,
a **configurable logo**, and a **secret-URL first-run onboarding** wizard. Until setup
is finished the public sees an *“under construction”* page.

Built to run on one **FrankenPHP** container, sized for a small VM (2 vCPU / 1 GB RAM).

---

## Features

1. **User management with dynamic roles & permissions** — create roles and
   permissions at runtime (no redeploy), assign them to users. Backed by
   [spatie/laravel-permission]. A `super-admin` role implicitly holds everything.
2. **URL shortener with click tracking** — create root-level short links (`/{code}`,
   custom or auto-generated), enable/disable them, and track total + unique clicks.
   Links are **scoped to their creator**; administrators see all.
3. **Home-page visitor analytics** — every hit on `/` is counted and split into
   **unique vs repeat visitors** (privacy-respecting hashed cookie + hashed IP),
   surfaced on the dashboard.
4. **Configurable branding + onboarding** — a one-time wizard at a secret URL
   (`/setup/{secret}`) sets the portal name & logo and creates the first
   super-administrator. Before completion every public route shows the construction page.

## Stack at a glance

| Layer        | Choice                                            |
|--------------|---------------------------------------------------|
| Runtime      | **FrankenPHP** (Caddy + embedded PHP 8.3, auto-HTTPS), one container |
| Framework    | **Laravel 12**, **Livewire 3** (TALL stack)       |
| RBAC         | **spatie/laravel-permission 6**                   |
| Database     | **SQLite** (WAL) — zero extra memory              |
| Frontend     | **Tailwind 3** + **Vite**                         |
| Cache/session| File-based (no Redis needed)                      |
| Container    | Multi-stage build, ~1 image, healthcheck on `/up` |
| CI/CD        | GitHub Actions → GHCR (builds & publishes image)  |

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
- The dev stack auto-creates `.env`, generates a key, and migrates/seeds the RBAC baseline.
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

Bring your own VM (anything you can SSH into). **CI builds the image** and pushes it to
**GitHub Container Registry**; you deploy by **pulling** it onto the VM over plain SSH —
no provider CLI, no local build, and the VM never builds.

```bash
cp deploy/config.env.example deploy/config.env   # set SSH_HOST/SSH_USER, IMAGE_OWNER, optional DOMAIN

make configure   # one-time: install Docker + 2 GB swap on the VM (over SSH)
git push         # GitHub Actions builds, tests & pushes the image to GHCR
make deploy      # VM pulls the CI-built image and runs it
```

Iterate later by pushing again (CI rebuilds) then `make deploy`. The deploy step
prints your one-time setup URL, e.g. `http://<SSH_HOST>/setup/<secret>`. Open it,
create the super-admin, and the portal goes live.

> Prefer to build locally instead of CI? `export GHCR_TOKEN=ghp_xxx` (write:packages)
> and run `make release` (= `make push` + `make deploy`). GHCR packages are private by
> default — make it public or `export GHCR_PULL_TOKEN=$GHCR_TOKEN` so the VM can pull.

Full walkthrough: [docs/USER_GUIDE.md](docs/USER_GUIDE.md).

## Project layout

```
app/Livewire/            Dashboard, Auth, Onboarding, Users, Roles, Permissions, ShortLinks
app/Http/Middleware/     EnsureOnboarded (the under-construction gate)
app/Support/             VisitTracker (home + short-link hit tracking)
app/Models/              User, ShortLink, ShortLinkClick, Visitor, HomeVisit, Setting
config/onboarding.php    Secret + default onboarding checklist
database/                Migrations, seeders (PermissionSeeder)
docker/                  Caddyfile, php tuning, entrypoints
Dockerfile               Multi-stage FrankenPHP image
docker-compose.yml       Production single-container stack
compose.dev.yaml         Docker-only dev stack (app + vite)
deploy/                  SSH deploy scripts (configure [one-time], build-push→GHCR, deploy)
docs/USER_GUIDE.md       Full setup & operations guide
```

[spatie/laravel-permission]: https://spatie.be/docs/laravel-permission
