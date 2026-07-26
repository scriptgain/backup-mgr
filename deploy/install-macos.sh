#!/usr/bin/env bash
# BackupMGR agent installer for macOS (Apple Silicon or Intel). Run:
#   curl -fsSL https://MASTER/downloads/install-macos.sh | sudo bash -s -- https://MASTER <enroll-token>
set -euo pipefail
MASTER="${1:?usage: install-macos.sh <master-url> <enroll-token>}"
TOKEN="${2:?usage: install-macos.sh <master-url> <enroll-token>}"
MASTER="${MASTER%/}"
DEST="${BACKUP_DIR:-/usr/local/backup}"
CFG="/usr/local/etc/backup/agent.json"
[ "$(id -u)" -eq 0 ] || { echo "Run with sudo."; exit 1; }
case "$(uname -m)" in
  arm64)  ARCH=arm64 ;;
  x86_64) ARCH=amd64 ;;
  *) echo "Unsupported arch: $(uname -m)"; exit 1 ;;
esac
echo "==> Downloading agent + kopia ($ARCH) from ${MASTER}/downloads/mac"
mkdir -p "$DEST" "$(dirname "$CFG")"
curl -fsSL "${MASTER}/downloads/mac/agent-${ARCH}" -o "$DEST/agent"
curl -fsSL "${MASTER}/downloads/mac/kopia-${ARCH}" -o "$DEST/kopia"
chmod +x "$DEST/agent" "$DEST/kopia"
xattr -dr com.apple.quarantine "$DEST/agent" "$DEST/kopia" 2>/dev/null || true
echo "==> Enrolling with the Manager"
"$DEST/agent" enroll -master "$MASTER" -token "$TOKEN" -config "$CFG"
echo "==> Installing launchd daemon"
PLIST=/Library/LaunchDaemons/dev.scriptgain.backup-agent.plist
cat > "$PLIST" <<PL
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0"><dict>
  <key>Label</key><string>dev.scriptgain.backup-agent</string>
  <key>ProgramArguments</key><array><string>${DEST}/agent</string><string>run</string><string>-config</string><string>${CFG}</string></array>
  <key>RunAtLoad</key><true/>
  <key>KeepAlive</key><true/>
  <key>StandardErrorPath</key><string>/var/log/backup-agent.log</string>
  <key>StandardOutPath</key><string>/var/log/backup-agent.log</string>
</dict></plist>
PL
launchctl unload "$PLIST" 2>/dev/null || true
launchctl load -w "$PLIST"
echo "==> Done. Agent enrolled + running. Logs: /var/log/backup-agent.log"
