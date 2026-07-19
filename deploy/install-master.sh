#!/usr/bin/env bash
#
# BackupMGR installer — provisions the control plane on a fresh
# Debian/Ubuntu server: PHP, MariaDB, nginx, Composer, the app, .env, database
# migration, queue worker + scheduler, and (optionally) a Let's Encrypt cert.
#
# Usage (run as root from the repo root, or clone first):
#   DOMAIN=backup.example.com ./deploy/install-master.sh
#   DOMAIN=backup.example.com SSL=1 EMAIL=you@example.com ./deploy/install-master.sh
#
# Idempotent: safe to re-run. Tested targets: Ubuntu 22.04/24.04, Debian 12.
set -euo pipefail

# ---- config (override via env) ----
APP_DIR="${APP_DIR:-/var/www/backup}"
DOMAIN="${DOMAIN:-}"
PHP_VER="${PHP_VER:-8.3}"
DB_NAME="${DB_NAME:-backupdb}"
DB_USER="${DB_USER:-backup}"
SSL="${SSL:-0}"
EMAIL="${EMAIL:-}"
# Default on-disk location for backups (filesystem repositories + local DB dumps).
BACKUP_STORE="${BACKUP_STORE:-/var/backups/backupmgr}"
SRC_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

[ "$(id -u)" -eq 0 ] || { echo "Run as root."; exit 1; }
[ -n "$DOMAIN" ] || { echo "Set DOMAIN=your.domain"; exit 1; }
command -v apt-get >/dev/null || { echo "This installer targets Debian/Ubuntu (apt)."; exit 1; }

log() { printf '\n\033[1;36m==> %s\033[0m\n' "$*"; }

log "Installing packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y software-properties-common ca-certificates curl unzip git gnupg
# ondrej PPA gives modern PHP on Ubuntu; sury does the same on Debian
# (Debian 12 ships PHP 8.2, so php${PHP_VER} needs the sury repo).
if grep -qi ubuntu /etc/os-release; then
  add-apt-repository -y ppa:ondrej/php
  apt-get update -y
elif grep -qi debian /etc/os-release; then
  install -d -m 0755 /etc/apt/keyrings
  curl -fsSL https://packages.sury.org/php/apt.gpg -o /etc/apt/keyrings/sury-php.gpg
  echo "deb [signed-by=/etc/apt/keyrings/sury-php.gpg] https://packages.sury.org/php $(. /etc/os-release; echo "$VERSION_CODENAME") main" > /etc/apt/sources.list.d/sury-php.list
  apt-get update -y
fi
apt-get install -y \
  "php${PHP_VER}-fpm" "php${PHP_VER}-cli" "php${PHP_VER}-mysql" "php${PHP_VER}-mbstring" \
  "php${PHP_VER}-xml" "php${PHP_VER}-curl" "php${PHP_VER}-zip" "php${PHP_VER}-bcmath" \
  "php${PHP_VER}-intl" "php${PHP_VER}-gd" \
  mariadb-server nginx

log "Installing Composer"
if ! command -v composer >/dev/null; then
  curl -sS https://getcomposer.org/installer | "php${PHP_VER}" -- --install-dir=/usr/local/bin --filename=composer
fi

log "Creating database"
DB_PASS="${DB_PASS:-$(openssl rand -base64 18 | tr -d '/+=' | cut -c1-20)}"
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1'; FLUSH PRIVILEGES;"

log "Deploying application to ${APP_DIR}"
mkdir -p "$APP_DIR"
rsync -a --delete \
  --exclude '.git' --exclude 'node_modules' --exclude 'agent' \
  --exclude '.env' --exclude 'storage/logs/*' \
  "$SRC_DIR"/ "$APP_DIR"/
cd "$APP_DIR"

log "Configuring environment"
# Laravel runtime dirs (git does not track empty dirs, so the deploy omits them).
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
if [ ! -f .env ]; then
  cp .env.example .env 2>/dev/null || touch .env
fi
set_env() { grep -q "^$1=" .env && sed -i "s|^$1=.*|$1=$2|" .env || echo "$1=$2" >> .env; }
set_env APP_NAME Backup
set_env APP_ENV production
set_env APP_DEBUG false
set_env APP_URL "https://${DOMAIN}"
set_env DB_CONNECTION mysql
set_env DB_HOST 127.0.0.1
set_env DB_PORT 3306
set_env DB_DATABASE "$DB_NAME"
set_env DB_USERNAME "$DB_USER"
set_env DB_PASSWORD "$DB_PASS"
set_env SESSION_DRIVER database
set_env QUEUE_CONNECTION database
set_env CACHE_STORE database

# .env must carry the DB config BEFORE composer runs — its post-autoload scripts
# (package:discover) boot Laravel and would otherwise fall back to defaults.
composer install --no-dev --optimize-autoloader --no-interaction
grep -q "^APP_KEY=base64" .env || "php${PHP_VER}" artisan key:generate --force

log "Migrating + bootstrapping"
"php${PHP_VER}" artisan migrate --force
# Non-fatal: seeds defaults but returns non-zero before the first admin exists.
"php${PHP_VER}" artisan backup:bootstrap || true

# Provision a default backup location and set it as the local-backup default.
log "Default backup location: ${BACKUP_STORE}"
mkdir -p "$BACKUP_STORE"
chown -R www-data:www-data "$BACKUP_STORE"
chmod 750 "$BACKUP_STORE"
"php${PHP_VER}" artisan tinker --execute="\App\Models\Setting::put('dbbackup_local_path', '${BACKUP_STORE}'); \App\Models\Setting::put('default_backup_path', '${BACKUP_STORE}');" 2>/dev/null || true

# Activate the license now if a key was supplied (LICENSE_KEY=... ./install-master.sh).
# Non-fatal: the panel runs and shows a banner until a valid key is set.
if [ -n "${LICENSE_KEY:-}" ]; then
  log "Activating license"
  "php${PHP_VER}" artisan backup:license "$LICENSE_KEY" || echo "License not yet valid; set it later with: php${PHP_VER} artisan backup:license <key>"
fi

"php${PHP_VER}" artisan config:cache
"php${PHP_VER}" artisan route:cache

log "Provisioning agent + kopia (so hosts can enroll to this Manager)"
# These binaries are gitignored build artifacts, so a fresh clone lacks them.
# Fetch the vendor agent (public /v1) + the official kopia release into public/downloads.
mkdir -p public/downloads
cp deploy/agent-install.sh public/downloads/agent-install.sh
curl -fsSL "${AGENT_URL:-https://scriptgain.com/v1/agent}" -o public/downloads/agent \
  || echo "!! agent download failed; place the agent binary at ${APP_DIR}/public/downloads/agent manually."
KOPIA_VER="${KOPIA_VER:-0.23.1}"
if curl -fsSL "https://github.com/kopia/kopia/releases/download/v${KOPIA_VER}/kopia-${KOPIA_VER}-linux-x64.tar.gz" -o /tmp/kopia.tgz; then
  tar xzf /tmp/kopia.tgz -C /tmp && cp /tmp/kopia-*/kopia public/downloads/kopia && rm -rf /tmp/kopia.tgz /tmp/kopia-*-linux-x64
else
  echo "!! kopia download failed; place a kopia binary at ${APP_DIR}/public/downloads/kopia manually."
fi
chmod +x public/downloads/agent public/downloads/kopia 2>/dev/null || true

log "Permissions"
chown -R www-data:www-data "$APP_DIR"
find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type d -exec chmod 775 {} \;

log "Configuring nginx"
cat > "/etc/nginx/sites-available/backup.conf" <<NGINX
server {
    listen 80;
    server_name ${DOMAIN};
    root ${APP_DIR}/public;
    index index.php;
    charset utf-8;
    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~ \.php\$ {
        fastcgi_pass unix:/run/php/php${PHP_VER}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
NGINX
ln -sf /etc/nginx/sites-available/backup.conf /etc/nginx/sites-enabled/backup.conf
nginx -t && systemctl reload nginx

log "Scheduler + queue worker"
# Scheduler via cron.
# Run the scheduler as www-data so self-updates keep app files www-data-owned.
( crontab -l 2>/dev/null | grep -v 'artisan schedule:run' || true ; \
  echo "* * * * * su -s /bin/sh www-data -c 'cd ${APP_DIR} && php${PHP_VER} artisan schedule:run >/dev/null 2>&1'" ) | crontab -
# Queue worker via systemd.
cat > /etc/systemd/system/backup-queue.service <<UNIT
[Unit]
Description=Backup queue worker
After=network.target mariadb.service

[Service]
User=www-data
Restart=always
ExecStart=/usr/bin/php${PHP_VER} ${APP_DIR}/artisan queue:work --sleep=3 --tries=3

[Install]
WantedBy=multi-user.target
UNIT
systemctl daemon-reload
systemctl enable --now backup-queue

if [ "$SSL" = "1" ]; then
  log "Issuing Let's Encrypt certificate"
  apt-get install -y certbot python3-certbot-nginx
  certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos ${EMAIL:+-m "$EMAIL"} ${EMAIL:+} || echo "certbot failed; run it manually."
fi

log "Done"
echo "BackupMGR installed at https://${DOMAIN}"
echo "Default backup location: ${BACKUP_STORE}"
echo "DB password + admin token are in ${APP_DIR}/.env and storage/app/private/bootstrap-token.txt"
echo "Create your admin user:  cd ${APP_DIR} && php${PHP_VER} artisan tinker  (User::create([...]))"
if [ -z "${LICENSE_KEY:-}" ]; then
  echo "Set your license key:    cd ${APP_DIR} && php${PHP_VER} artisan backup:license <key>   (buy at https://scriptgain.com/products/backup-manager)"
fi
