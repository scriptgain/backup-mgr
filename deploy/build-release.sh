#!/usr/bin/env bash
#
# Build a distributable BackupMGR release for the scriptgain.com download.
# Produces  dist/backup-manager-<version>.zip  containing a clean source tree
# (installer runs composer/npm on the target), the prebuilt agent + kopia
# binaries the Manager serves to hosts, and a VERSION stamp.
#
# Usage:   deploy/build-release.sh 1.2.0
#          deploy/build-release.sh            # reads ./VERSION
#
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

VERSION="${1:-$(cat VERSION 2>/dev/null || true)}"
[ -n "$VERSION" ] || { echo "Set a version: deploy/build-release.sh <version>  (or create ./VERSION)"; exit 1; }
VERSION="${VERSION#v}"

NAME="backup-manager-${VERSION}"
OUT="$ROOT/dist"
STAGE="$OUT/$NAME"
rm -rf "$STAGE"; mkdir -p "$STAGE"

echo "==> Staging source tree ($NAME)"
# Ship source; the installer builds vendor/assets on the target. Exclude dev,
# secrets, local state, and the giant node_modules/vendor.
rsync -a \
  --exclude='.git' --exclude='.env' --exclude='.env.*' \
  --exclude='node_modules' --exclude='vendor' \
  --exclude='dist' --exclude='tests' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/*' --exclude='storage/framework/sessions/*' --exclude='storage/framework/views/*' \
  --exclude='storage/app/backups/*' \
  --exclude='agent/bin/*' \
  ./ "$STAGE/"

echo "==> Bundling agent + kopia binaries"
mkdir -p "$STAGE/agent/bin"
if [ -f agent/bin/agent ] && [ -f agent/bin/kopia ]; then
  cp agent/bin/agent agent/bin/kopia "$STAGE/agent/bin/"
  chmod +x "$STAGE/agent/bin/agent" "$STAGE/agent/bin/kopia"
else
  echo "!! agent/bin/agent or kopia missing — build the agent first (see deploy/local/fetch-kopia.sh + go build)."; exit 1
fi

echo "==> Writing VERSION + manifest"
printf '%s\n' "$VERSION" > "$STAGE/VERSION"
cat > "$STAGE/RELEASE.txt" <<TXT
BackupMGR ${VERSION}
Self-hosted backup platform by scriptgain.com

Install (fresh Debian/Ubuntu server, as root):
  DOMAIN=backup.example.com ./deploy/install-master.sh
  # add SSL=1 EMAIL=you@example.com for a Let's Encrypt cert

License:
  After install, set your key:  php artisan backup:license <YOUR-KEY>
  Buy / manage at https://scriptgain.com/products/backup-manager
TXT

echo "==> Zipping"
mkdir -p "$OUT"
rm -f "$OUT/$NAME.zip"
if command -v zip >/dev/null; then
  ( cd "$OUT" && zip -rqX "$NAME.zip" "$NAME" )
else
  # Portable fallback when the zip binary is absent.
  ( cd "$OUT" && python3 - "$NAME" <<'PY'
import os, sys, zipfile
name = sys.argv[1]
with zipfile.ZipFile(name + ".zip", "w", zipfile.ZIP_DEFLATED) as z:
    for root, _, files in os.walk(name):
        for f in files:
            p = os.path.join(root, f)
            zi = zipfile.ZipInfo.from_file(p, p)
            if os.access(p, os.X_OK):
                zi.external_attr = (0o755 << 16)
            with open(p, "rb") as fh:
                z.writestr(zi, fh.read(), zipfile.ZIP_DEFLATED)
PY
  )
fi
rm -rf "$STAGE"

SIZE=$(du -h "$OUT/$NAME.zip" | cut -f1)
echo "==> Built $OUT/$NAME.zip ($SIZE)"
echo "    sha256: $(sha256sum "$OUT/$NAME.zip" | cut -d' ' -f1)"
