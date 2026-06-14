#!/usr/bin/env bash
# Runs ON the VM. Installs Docker Engine + Compose plugin and a swap file.
# Idempotent: safe to re-run.
set -euo pipefail

SWAP_SIZE="${1:-2G}"

echo "==> Updating apt and base packages…"
sudo apt-get update -y
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y \
  ca-certificates curl gnupg

# --- Swap (critical on a 1 GB VM, especially for building the image) --------
if ! sudo swapon --show | grep -q '/swapfile'; then
  echo "==> Creating ${SWAP_SIZE} swap file…"
  sudo fallocate -l "${SWAP_SIZE}" /swapfile || sudo dd if=/dev/zero of=/swapfile bs=1M count=2048
  sudo chmod 600 /swapfile
  sudo mkswap /swapfile
  sudo swapon /swapfile
  grep -q '/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
  # Gentle swappiness so RAM is preferred but swap saves us under build pressure.
  echo 'vm.swappiness=10' | sudo tee /etc/sysctl.d/99-swappiness.conf
  sudo sysctl -p /etc/sysctl.d/99-swappiness.conf || true
else
  echo "==> Swap already configured."
fi

# --- Docker Engine + Compose plugin (official repo) -------------------------
if ! command -v docker >/dev/null 2>&1; then
  echo "==> Installing Docker Engine…"
  sudo install -m 0755 -d /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/debian/gpg | \
    sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
  sudo chmod a+r /etc/apt/keyrings/docker.gpg
  echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
https://download.docker.com/linux/debian $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
    sudo tee /etc/apt/sources.list.d/docker.list >/dev/null
  sudo apt-get update -y
  sudo apt-get install -y docker-ce docker-ce-cli containerd.io \
                          docker-buildx-plugin docker-compose-plugin
else
  echo "==> Docker already installed."
fi

sudo systemctl enable --now docker
sudo usermod -aG docker "$USER" || true

# --- Trim Docker logs so they can't fill the small disk ---------------------
if [ ! -f /etc/docker/daemon.json ]; then
  echo '{"log-driver":"json-file","log-opts":{"max-size":"10m","max-file":"3"}}' | \
    sudo tee /etc/docker/daemon.json >/dev/null
  sudo systemctl restart docker
fi

echo "==> Bootstrap complete. Docker: $(docker --version)"
