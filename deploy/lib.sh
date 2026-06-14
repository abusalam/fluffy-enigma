#!/usr/bin/env bash
# Shared helpers for the deploy scripts. Sourced, not executed.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]:-$0}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

c_blue='\033[0;34m'; c_green='\033[0;32m'; c_yellow='\033[0;33m'; c_red='\033[0;31m'; c_off='\033[0m'
log()  { echo -e "${c_blue}==>${c_off} $*"; }
ok()   { echo -e "${c_green}✓${c_off} $*"; }
warn() { echo -e "${c_yellow}!${c_off} $*"; }
die()  { echo -e "${c_red}✗ $*${c_off}" >&2; exit 1; }

load_config() {
  local cfg="$SCRIPT_DIR/config.env"
  [[ -f "$cfg" ]] || die "Missing deploy/config.env — copy deploy/config.env.example and edit it."
  # shellcheck disable=SC1090
  source "$cfg"
  : "${SSH_HOST:?set SSH_HOST}" "${SSH_USER:?set SSH_USER}" "${REMOTE_DIR:?set REMOTE_DIR}"
}

require() { command -v "$1" >/dev/null 2>&1 || die "'$1' is required but not installed."; }

# Full image repo (GHCR owner must be lowercase).
image_repo() {
  local owner_lc
  owner_lc="$(printf '%s' "${IMAGE_OWNER:?set IMAGE_OWNER in config.env}" | tr '[:upper:]' '[:lower:]')"
  printf '%s/%s/%s' "${IMAGE_REGISTRY:?set IMAGE_REGISTRY}" "$owner_lc" "${IMAGE_NAME:?set IMAGE_NAME}"
}

# Common SSH/SCP options.
_ssh_common=(-o StrictHostKeyChecking=accept-new -o ConnectTimeout=15)

# Run a command (or heredoc on stdin) on the VM over SSH.
remote_sh() {
  local args=("${_ssh_common[@]}")
  [[ -n "${SSH_KEY:-}" ]]  && args+=(-i "$SSH_KEY")
  [[ -n "${SSH_PORT:-}" ]] && args+=(-p "$SSH_PORT")
  ssh "${args[@]}" "${SSH_USER}@${SSH_HOST}" "$@"
}

# Copy a local file to the VM.
remote_cp() {
  local src="$1" dst="$2"
  local args=("${_ssh_common[@]}")
  [[ -n "${SSH_KEY:-}" ]]  && args+=(-i "$SSH_KEY")
  [[ -n "${SSH_PORT:-}" ]] && args+=(-P "$SSH_PORT")   # scp uses -P (uppercase)
  scp "${args[@]}" "$src" "${SSH_USER}@${SSH_HOST}:$dst"
}

# Public host used for APP_URL / printed links.
public_host() { printf '%s' "${DOMAIN:-$SSH_HOST}"; }
