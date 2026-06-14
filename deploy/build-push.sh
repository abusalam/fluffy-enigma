#!/usr/bin/env bash
# Build the image locally and push it to GHCR as :latest and :<timestamp>.
# The VM never builds — it pulls this image (see deploy.sh).
set -euo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"
load_config
require docker

REPO="$(image_repo)"
TS="$(date -u +%Y%m%d-%H%M%S)"

: "${GHCR_TOKEN:?export GHCR_TOKEN with a GitHub PAT that has write:packages}"

log "Logging in to ${IMAGE_REGISTRY} as ${IMAGE_OWNER}…"
printf '%s' "$GHCR_TOKEN" | docker login "$IMAGE_REGISTRY" -u "$IMAGE_OWNER" --password-stdin

log "Building ${REPO}:${TS} (+ :latest) for linux/amd64…"
# e2-micro is linux/amd64. On an arm64 host this uses emulation via buildx.
docker build --platform linux/amd64 \
  -t "${REPO}:${TS}" \
  -t "${REPO}:latest" \
  "$ROOT_DIR"

log "Pushing…"
docker push "${REPO}:${TS}"
docker push "${REPO}:latest"

# Record the immutable tag so deploy.sh can pin to it.
printf '%s:%s\n' "$REPO" "$TS" > "$SCRIPT_DIR/.last-image"

ok "Pushed ${REPO}:${TS} and ${REPO}:latest"
echo
echo "Next:  ./deploy/deploy.sh        # pulls ${REPO}:${TS} onto the VM"
echo "Tip:   make the GHCR package public, or 'export GHCR_PULL_TOKEN=\$GHCR_TOKEN' for a private pull."
