#!/usr/bin/env bash
# Deploy by PULLING the pre-built image from GHCR onto the VM, over plain SSH.
# Run ./deploy/build-push.sh first. Pass an explicit image ref to roll back:
#   ./deploy/deploy.sh ghcr.io/owner/scheme-monitor:20260614-001122
set -euo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]:-$0}")" && pwd)/lib.sh"
load_config
require ssh
require scp

REPO="$(image_repo)"
# Prefer an explicit arg, then the last pushed tag, then :latest.
IMAGE="${1:-$(cat "$SCRIPT_DIR/.last-image" 2>/dev/null || echo "${REPO}:latest")}"

HOST="$(public_host)"
DOMAIN="${DOMAIN:-}"
PULL_TOKEN="${GHCR_PULL_TOKEN:-}"

log "Deploying image: ${IMAGE}  →  ${SSH_USER}@${SSH_HOST}"

log "Uploading compose file…"
remote_cp "$ROOT_DIR/docker-compose.yml" "/tmp/docker-compose.yml"

remote_sh "bash -s" <<REMOTE
set -euo pipefail
sudo mkdir -p '${REMOTE_DIR}'
sudo chown -R \$USER:\$USER '${REMOTE_DIR}'
cp /tmp/docker-compose.yml '${REMOTE_DIR}/docker-compose.yml'
cd '${REMOTE_DIR}'

# --- .env: created once with fresh secrets, image ref kept current ----------
if [ ! -f .env ]; then
  echo '   creating .env with fresh secrets'
  APP_KEY="base64:\$(openssl rand -base64 32)"
  SECRET="\$(openssl rand -hex 24)"
  if [ -n '${DOMAIN}' ]; then SRV='${DOMAIN}'; URL='https://${DOMAIN}'; else SRV=':80'; URL='http://${HOST}'; fi
  cat > .env <<ENV
APP_NAME="Scheme Monitor"
APP_ENV=production
APP_DEBUG=false
APP_KEY=\${APP_KEY}
APP_URL=\${URL}
APP_ONBOARDING_SECRET=\${SECRET}
SERVER_NAME="\${SRV}"
DB_CONNECTION=sqlite
DB_DATABASE=/app/storage/app/database/database.sqlite
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
LOG_STACK=stderr
LOG_LEVEL=warning
SEED_DEMO_DATA=true
APP_IMAGE=${IMAGE}
ENV
else
  echo '   updating APP_IMAGE in existing .env'
  if grep -q '^APP_IMAGE=' .env; then
    sed -i "s|^APP_IMAGE=.*|APP_IMAGE=${IMAGE}|" .env
  else
    echo 'APP_IMAGE=${IMAGE}' >> .env
  fi
fi

# --- Registry auth (only needed for a PRIVATE GHCR package) -----------------
if [ -n '${PULL_TOKEN}' ]; then
  echo '   logging the VM in to ${IMAGE_REGISTRY}'
  printf '%s' '${PULL_TOKEN}' | sudo docker login '${IMAGE_REGISTRY}' -u '${IMAGE_OWNER}' --password-stdin
fi

echo '   pulling image…'
sudo docker compose pull
echo '   starting…'
sudo docker compose up -d --no-build
sudo docker image prune -f >/dev/null 2>&1 || true
REMOTE

log "Fetching the onboarding setup URL…"
SECRET="$(remote_sh "grep '^APP_ONBOARDING_SECRET=' '${REMOTE_DIR}/.env' | cut -d= -f2-" | tr -d '\r"')"

BASE_URL="http://${HOST}"
[[ -n "$DOMAIN" ]] && BASE_URL="https://${DOMAIN}"

echo
ok "Deployed ${IMAGE}"
echo "  Public site (under construction until setup): ${BASE_URL}/"
echo -e "  ${c_yellow}One-time setup wizard:${c_off} ${BASE_URL}/setup/${SECRET}"
echo
echo "  Re-deploy a new build:  ./deploy/build-push.sh && ./deploy/deploy.sh   (or: make release)"
echo "  Tail logs over SSH:     ssh ${SSH_USER}@${SSH_HOST} 'cd ${REMOTE_DIR} && sudo docker compose logs -f --tail=100 app'"
