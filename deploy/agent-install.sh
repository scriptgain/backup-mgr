#!/usr/bin/env bash
#
# Backup agent installer. Run on the host you want to back up:
#
#   curl -fsSL https://MASTER/downloads/agent-install.sh | sudo bash -s -- https://MASTER <enroll-token>
#
# Downloads the agent + bundled kopia from the Manager, enrolls this host, and
# installs a systemd service that polls for backup jobs. Linux x86_64.
set -euo pipefail

MASTER="${1:?usage: agent-install.sh <master-url> <enroll-token>}"
TOKEN="${2:?usage: agent-install.sh <master-url> <enroll-token>}"
MASTER="${MASTER%/}"
DEST="${BACKUP_DIR:-/opt/backup}"
CFG="/etc/backup/agent.json"

[ "$(id -u)" -eq 0 ] || { echo "Run as root (sudo)."; exit 1; }
command -v curl >/dev/null || { echo "curl is required."; exit 1; }

echo "==> Downloading agent + kopia from ${MASTER}/downloads"
mkdir -p "$DEST" /etc/backup
curl -fsSL "${MASTER}/downloads/agent" -o "$DEST/agent"
curl -fsSL "${MASTER}/downloads/kopia" -o "$DEST/kopia"
chmod +x "$DEST/agent" "$DEST/kopia"

echo "==> Enrolling with the Manager"
"$DEST/agent" enroll -master "$MASTER" -token "$TOKEN" -config "$CFG"

echo "==> Installing systemd service"
cat > /etc/systemd/system/backup-agent.service <<UNIT
[Unit]
Description=Backup backup agent
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
