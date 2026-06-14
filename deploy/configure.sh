#!/usr/bin/env bash
# ONE-TIME VM configuration over SSH: install Docker + Compose and a swap file.
# Run this once against a fresh VM that you can already SSH into. Idempotent.
set -euo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]:-$0}")" && pwd)/lib.sh"
load_config
require ssh
require scp

log "Copying bootstrap script to ${SSH_USER}@${SSH_HOST}…"
remote_cp "$SCRIPT_DIR/remote/bootstrap.sh" "/tmp/bootstrap.sh"

log "Running one-time configuration (Docker + ${SWAP_SIZE:-2G} swap)…"
remote_sh "chmod +x /tmp/bootstrap.sh && /tmp/bootstrap.sh '${SWAP_SIZE:-2G}'"

ok "VM configured."
echo "Next:  ./deploy/build-push.sh && ./deploy/deploy.sh   (or: make release)"
