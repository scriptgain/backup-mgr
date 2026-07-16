#!/usr/bin/env bash
# Fetch the pinned kopia binary into agent/bin/. Idempotent.
# The agent drives this binary; we pin an exact version per agent build.
set -euo pipefail

KOPIA_VERSION="0.23.1"
ARCH="linux-x64" # adjust for other targets: linux-arm64, etc.

here="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
dest="$here/../../agent/bin"
mkdir -p "$dest"

if [ -x "$dest/kopia" ] && "$dest/kopia" --version 2>/dev/null | grep -q "$KOPIA_VERSION"; then
  echo "kopia $KOPIA_VERSION already present"
  exit 0
fi

url="https://github.com/kopia/kopia/releases/download/v${KOPIA_VERSION}/kopia-${KOPIA_VERSION}-${ARCH}.tar.gz"
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT

echo "downloading kopia $KOPIA_VERSION ($ARCH)..."
curl -fsSL -o "$tmp/kopia.tgz" "$url"
tar -xzf "$tmp/kopia.tgz" --strip-components=1 -C "$dest" "kopia-${KOPIA_VERSION}-${ARCH}/kopia"
chmod +x "$dest/kopia"
"$dest/kopia" --version
