#!/usr/bin/env bash
#
# BackupMGR agent installer. Run on the host you want to back up:
#
#   curl -fsSL https://MASTER/downloads/agent-install.sh | sudo bash -s -- https://MASTER <enroll-token>
#
# Works on any systemd Linux x86_64: Ubuntu/Debian (apt), AlmaLinux/Rocky/RHEL/
# CentOS/Fedora (dnf/yum). The agent + kopia are fully static, so there are no
# glibc/distro dependencies.
set -euo pipefail

MASTER="${1:?usage: agent-install.sh <master-url> <enroll-token>}"
TOKEN="${2:?usage: agent-install.sh <master-url> <enroll-token>}"
MASTER="${MASTER%/}"
DEST="${BACKUP_DIR:-/opt/backup}"
CFG="/etc/backup/agent.json"

[ "$(id -u)" -eq 0 ] || { echo "Run as root (sudo)."; exit 1; }

# curl is the only external dependency; install it via whatever package manager exists.
if ! command -v curl >/dev/null; then
  echo "==> curl not found, installing it"
  if   command -v dnf     >/dev/null; then dnf install -y curl
  elif command -v yum     >/dev/null; then yum install -y curl
  elif command -v apt-get >/dev/null; then apt-get update && apt-get install -y curl
  elif command -v zypper  >/dev/null; then zypper install -y curl
  else echo "curl is required and no supported package manager (dnf/yum/apt/zypper) was found."; exit 1; fi
fi

echo "==> Downloading agent + kopia from ${MASTER}/downloads"
mkdir -p "$DEST" /etc/backup
curl -fsSL "${MASTER}/downloads/agent" -o "$DEST/agent"
curl -fsSL "${MASTER}/downloads/kopia" -o "$DEST/kopia"
chmod +x "$DEST/agent" "$DEST/kopia"

# SELinux (AlmaLinux/Rocky/RHEL enforce by default): label the binaries executable
# so the systemd service is allowed to exec them.
if command -v getenforce >/dev/null && [ "$(getenforce)" != "Disabled" ]; then
  echo "==> SELinux is $(getenforce); labeling binaries bin_t"
  command -v chcon >/dev/null && chcon -t bin_t "$DEST/agent" "$DEST/kopia" 2>/dev/null || true
fi

echo "==> Enrolling with the Manager"
"$DEST/agent" enroll -master "$MASTER" -token "$TOKEN" -config "$CFG"

echo "==> Installing systemd service"
cat > /etc/systemd/system/backup-agent.service <<UNIT
[Unit]
Description=BackupMGR agent
After=network-online.target
Wants=network-online.target

[Service]
ExecStart=${DEST}/agent run -config ${CFG}
Restart=always
RestartSec=5
User=root

[Install]
WantedBy=multi-user.target
UNIT

systemctl daemon-reload
systemctl enable --now backup-agent
echo "==> Done. The agent is enrolled and running (systemctl status backup-agent)."
