#!/usr/bin/env bash
#
# Little Steps — one-time server preparation on the shared vault box.
#
# Run once, as the `ubuntu` user, with the whole deploy/ directory present:
#
#   scp -i <key> -r deploy ubuntu@$SERVER_IP:/tmp/
#   ssh -i <key> ubuntu@$SERVER_IP 'bash /tmp/deploy/bootstrap-server.sh'
#
# Does everything that does not need a browser: swapfile, directories, the
# database and its user, and laravel/.env with real secrets. It reads vault's
# Redis password and MariaDB root password straight out of /opt/vault/laravel/.env,
# so nothing has to be copied by hand.
#
# Safe to re-run. It never regenerates an existing APP_KEY — doing so would
# invalidate every session and every encrypted value already stored.
set -euo pipefail

APP_DIR=/opt/littlesteps
VAULT_ENV=/opt/vault/laravel/.env
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

say() { printf '\n\033[1m==> %s\033[0m\n' "$1"; }
warn() { printf '\033[33m    %s\033[0m\n' "$1"; }

# ---------------------------------------------------------------------------
# Preflight
# ---------------------------------------------------------------------------
say "Checking prerequisites"

[ -f "$VAULT_ENV" ] || { echo "!!! $VAULT_ENV not found — is this the vault server?"; exit 1; }
[ -f "$SCRIPT_DIR/.env.server.example" ] || { echo "!!! .env.server.example not next to this script"; exit 1; }
docker ps >/dev/null 2>&1 || { echo "!!! cannot talk to docker as $(whoami)"; exit 1; }
docker inspect vault_database >/dev/null 2>&1 || { echo "!!! vault_database is not running"; exit 1; }
echo "    ok"

# Read vault's secrets. cut -d= -f2- keeps anything after the first '=', and we
# strip surrounding quotes if the value has them.
read_env() {
    local key="$1" file="$2" value
    value=$(grep -E "^${key}=" "$file" | head -1 | cut -d= -f2-)
    value="${value%\"}"; value="${value#\"}"
    value="${value%\'}"; value="${value#\'}"
    printf '%s' "$value"
}

VAULT_REDIS_PASSWORD=$(read_env REDIS_PASSWORD "$VAULT_ENV")
VAULT_DB_ROOT_PASSWORD=$(read_env DB_PASSWORD "$VAULT_ENV")

[ -n "$VAULT_REDIS_PASSWORD" ] || { echo "!!! could not read REDIS_PASSWORD from $VAULT_ENV"; exit 1; }
[ -n "$VAULT_DB_ROOT_PASSWORD" ] || { echo "!!! could not read DB_PASSWORD from $VAULT_ENV"; exit 1; }

# ---------------------------------------------------------------------------
# 1. Swap
#
# The box has ~730 MB free and no swap. Our three containers want ~350 MB of
# that. Without swap, one oversized image conversion invokes the OOM killer,
# and it picks its victim by score — it may well choose vault_database.
# ---------------------------------------------------------------------------
say "Swapfile"
if [ -f /swapfile ]; then
    echo "    already present, leaving it alone"
else
    sudo fallocate -l 2G /swapfile
    sudo chmod 600 /swapfile
    sudo mkswap /swapfile >/dev/null
    sudo swapon /swapfile
    echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab >/dev/null
    echo "    created 2 GB"
fi

if ! grep -q vm.swappiness /etc/sysctl.d/99-swappiness.conf 2>/dev/null; then
    echo 'vm.swappiness=10' | sudo tee /etc/sysctl.d/99-swappiness.conf >/dev/null
    sudo sysctl -q vm.swappiness=10
    echo "    swappiness set to 10 (swap is a safety net, not a first resort)"
fi
free -m | awk '/Swap:/ {printf "    swap total: %s MB\n", $2}'

# ---------------------------------------------------------------------------
# 2. Directories
# ---------------------------------------------------------------------------
say "Directories"
sudo mkdir -p "$APP_DIR"/{laravel/storage,php}
sudo chown -R ubuntu:ubuntu "$APP_DIR"
echo "    $APP_DIR ready"

# ---------------------------------------------------------------------------
# 3. Database and user
#
# Scoped to littlesteps.* on purpose — the app must not be able to read
# vault's tables.
# ---------------------------------------------------------------------------
say "Database"
if [ -f "$APP_DIR/laravel/.env" ]; then
    # Re-run: keep the password already in use rather than rotating it.
    LS_DB_PASSWORD=$(read_env DB_PASSWORD "$APP_DIR/laravel/.env")
    echo "    reusing the password from the existing .env"
else
    # hex, so it is safe to drop into SQL and into .env without quoting
    LS_DB_PASSWORD=$(openssl rand -hex 24)
    echo "    generated a new password"
fi

docker exec -i vault_database mariadb -uroot -p"$VAULT_DB_ROOT_PASSWORD" <<SQL
CREATE DATABASE IF NOT EXISTS littlesteps CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'littlesteps'@'%' IDENTIFIED BY '${LS_DB_PASSWORD}';
ALTER USER 'littlesteps'@'%' IDENTIFIED BY '${LS_DB_PASSWORD}';
GRANT ALL PRIVILEGES ON littlesteps.* TO 'littlesteps'@'%';
FLUSH PRIVILEGES;
SQL
echo "    database 'littlesteps' and user 'littlesteps'@'%' ready (scoped to littlesteps.*)"

# ---------------------------------------------------------------------------
# 4. laravel/.env
# ---------------------------------------------------------------------------
say "Environment file"
if [ -f "$APP_DIR/laravel/.env" ]; then
    warn "$APP_DIR/laravel/.env exists — leaving it untouched."
    warn "APP_KEY is never regenerated: a new one would invalidate every"
    warn "session and every encrypted value already in the database."
else
    # php artisan key:generate is 32 random bytes, base64-encoded.
    APP_KEY="base64:$(openssl rand -base64 32)"

    # Append rather than sed the three secrets in: values may contain / + = or
    # other characters that would need escaping in a sed replacement. .env does
    # not care about ordering.
    {
        grep -v -E '^(APP_KEY|DB_PASSWORD|REDIS_PASSWORD)=' "$SCRIPT_DIR/.env.server.example"
        echo ""
        echo "# --- Written by bootstrap-server.sh ---"
        echo "APP_KEY=${APP_KEY}"
        echo "DB_PASSWORD=${LS_DB_PASSWORD}"
        echo "REDIS_PASSWORD=${VAULT_REDIS_PASSWORD}"
    } > "$APP_DIR/laravel/.env"

    chmod 600 "$APP_DIR/laravel/.env"
    echo "    written with a fresh APP_KEY, the new DB password, and vault's Redis password"
fi

# ---------------------------------------------------------------------------
# 5. PHP overrides
# ---------------------------------------------------------------------------
say "PHP overrides"
cp "$SCRIPT_DIR/php/zz-server.ini" "$SCRIPT_DIR/php/zz-server.conf" "$APP_DIR/php/"
echo "    memory_limit 384M, pm.max_children 4"

# ---------------------------------------------------------------------------
# 6. Compose file and deploy script
# ---------------------------------------------------------------------------
say "Compose file and deploy script"
cp "$SCRIPT_DIR/docker-compose.server.yml" "$APP_DIR/docker-compose.yml"
cp "$SCRIPT_DIR/deploy.sh" "$APP_DIR/deploy.sh"
chmod +x "$APP_DIR/deploy.sh"
echo "    in place (CI overwrites both on every deploy)"

# ---------------------------------------------------------------------------
# Done
# ---------------------------------------------------------------------------
say "Server is ready"
cat <<'NEXT'

This box needs nothing further. GHCR authentication is NOT set up here on
purpose — the deploy workflow logs in with its own short-lived token on every
run, so there is no standing credential sitting on the server to leak or
rotate.

What is left, from your Mac:

  1. DNS       An A record for the app hostname pointing here. Do this BEFORE
               the Caddy step: Caddy asks Let's Encrypt for a certificate the
               moment it loads the route, and failures count against the rate
               limit.

  2. Secrets   Repo secrets SSH_HOST, SSH_USER and SSH_PRIVATE_KEY
               (the .pem verbatim, BEGIN/END lines included).

  3. Route     cd vault/ansible && ./update.sh <server-ip>

  4. Deploy    cd littlesteps-web && git push origin main

NEXT
