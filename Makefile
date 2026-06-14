# All commands run inside Docker — no PHP/Composer/Node required on the host.
DEV := docker compose -f compose.dev.yaml
PROD := docker compose

.DEFAULT_GOAL := help

.PHONY: help dev dev-down dev-logs build up down logs ps shell \
        artisan composer npm tinker test migrate fresh seed key optimize \
        configure push deploy release

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
	  awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

## ----- Development (Docker-only) -----
dev: ## Start the dev stack (app:8000, vite:5173)
	$(DEV) up --build

dev-down: ## Stop & remove the dev stack
	$(DEV) down

dev-logs: ## Tail dev app logs
	$(DEV) logs -f app

shell: ## Shell into the dev app container
	$(DEV) exec app sh

artisan: ## Run artisan, e.g. make artisan c="migrate:status"
	$(DEV) exec app php artisan $(c)

composer: ## Run composer, e.g. make composer c="require vendor/pkg"
	$(DEV) exec app composer $(c)

npm: ## Run npm in the vite container, e.g. make npm c="run build"
	$(DEV) exec vite npm $(c)

tinker: ## Open Tinker REPL
	$(DEV) exec app php artisan tinker

test: ## Run the test suite
	$(DEV) exec app php artisan test

migrate: ## Run migrations
	$(DEV) exec app php artisan migrate

fresh: ## Drop & rebuild the database with seeds
	$(DEV) exec app php artisan migrate:fresh --seed

seed: ## Seed demo schemes
	$(DEV) exec app php artisan db:seed --class='Database\Seeders\DemoSchemeSeeder'

key: ## Generate APP_KEY
	$(DEV) exec app php artisan key:generate

optimize: ## Clear all caches
	$(DEV) exec app php artisan optimize:clear

## ----- Production (single container) -----
build: ## Build the production image locally
	$(PROD) build

up: ## Start production stack locally (detached)
	$(PROD) up -d --build

down: ## Stop production stack
	$(PROD) down

logs: ## Tail production logs (local)
	$(PROD) logs -f app

ps: ## Show container status
	$(PROD) ps

## ----- Registry + remote deploy (GHCR + SSH) -----
configure: ## One-time VM setup over SSH (Docker + swap)
	./deploy/configure.sh

push: ## Build & push image to GHCR (needs GHCR_TOKEN + deploy/config.env)
	./deploy/build-push.sh

deploy: ## Pull the pushed image onto the VM and (re)launch (over SSH)
	./deploy/deploy.sh

release: push deploy ## Build+push, then deploy to the VM
