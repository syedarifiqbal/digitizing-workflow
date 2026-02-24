#!/usr/bin/env bash
# ================================================================
# deploy.sh — Deploy Digitizing Workflow to Namecheap shared hosting
#
# Usage:
#   1. cp .env.deploy.example .env.deploy  — then fill in your credentials
#   2. chmod +x deploy.sh
#   3. ./deploy.sh                  — sync only changed files (fast, ~seconds)
#      ./deploy.sh --with-vendor    — also sync vendor/ (when composer.json changed)
#
# How it works:
#   Uses rsync over SSH — only files that changed are transferred.
#   First run uploads everything; every run after is just the diff.
# ================================================================

set -euo pipefail

# -- Terminal colours --
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
BLUE='\033[0;34m'; BOLD='\033[1m'; NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_TMP="/tmp/.digitizing_env_production"

step() { echo -e "\n${BOLD}${BLUE}━━ $* ${NC}"; }
ok()   { echo -e "   ${GREEN}✓${NC}  $*"; }
warn() { echo -e "   ${YELLOW}⚠${NC}   $*"; }
die()  { echo -e "\n${RED}[ERROR]${NC} $*" >&2; exit 1; }

# ================================================================
# CONFIGURATION — Loaded from .env.deploy (never committed)
# ================================================================
ENV_DEPLOY="$SCRIPT_DIR/.env.deploy"
[[ -f "$ENV_DEPLOY" ]] || die ".env.deploy not found.\n\n  cp .env.deploy.example .env.deploy\n  # then fill in your credentials\n"
# shellcheck source=.env.deploy
source "$ENV_DEPLOY"
# ================================================================

# ================================================================
# Flag parsing
# ================================================================
INCLUDE_VENDOR=false
for arg in "$@"; do
  case $arg in
    --with-vendor) INCLUDE_VENDOR=true ;;
    *) die "Unknown flag: $arg\n\nUsage: ./deploy.sh [--with-vendor]" ;;
  esac
done

# ================================================================
# Preflight checks
# ================================================================
[[ -z "${SSH_USER:-}"    ]] && die "SSH_USER is not set in .env.deploy"
[[ -z "${SSH_HOST:-}"    ]] && die "SSH_HOST is not set in .env.deploy"
[[ -z "${DB_PASSWORD:-}" ]] && die "DB_PASSWORD is not set in .env.deploy"
command -v npm   >/dev/null 2>&1 || die "npm not found. Is Node.js installed?"
command -v rsync >/dev/null 2>&1 || die "rsync not found. Run: brew install rsync"
command -v ssh   >/dev/null 2>&1 || die "ssh not found."
command -v scp   >/dev/null 2>&1 || die "scp not found."

echo ""
echo -e "${BOLD}${GREEN}╔══════════════════════════════════════════╗${NC}"
echo -e "${BOLD}${GREEN}║   Digitizing Workflow — Deploy Script    ║${NC}"
echo -e "${BOLD}${GREEN}╚══════════════════════════════════════════╝${NC}"
echo -e "   Target : ${BOLD}$SSH_USER@$SSH_HOST${NC}"
echo -e "   URL    : ${BOLD}$APP_URL${NC}"
if [ "$INCLUDE_VENDOR" = "true" ]; then
  echo -e "   Vendor  : ${YELLOW}INCLUDED (--with-vendor)${NC}"
else
  echo -e "   Vendor  : skipped  (run with --with-vendor if composer.json changed)"
fi
echo ""

# ================================================================
# STEP 1 — Build frontend assets
# ================================================================
step "1 / 4  Building frontend assets"
cd "$SCRIPT_DIR"
export NVM_DIR="$HOME/.nvm"
# shellcheck source=/dev/null
[ -s "$NVM_DIR/nvm.sh" ] && source "$NVM_DIR/nvm.sh"
command -v nvm >/dev/null 2>&1 || die "nvm not found. Is nvm installed?"
nvm use 22
npm run build
ok "Assets compiled → public/build/"

# ================================================================
# STEP 2 — Sync files to server via rsync
#   rsync only transfers files that changed — no zip, no extract.
# ================================================================
step "2 / 4  Syncing changed files to server"

RSYNC_EXCLUDES=(
  --exclude='.git/'
  --exclude='.env'
  --exclude='deploy.sh'
  --exclude='node_modules/'
  --exclude='resources/js/'
  --exclude='public/hot'
  --exclude='bootstrap/cache/'          # generated on server — never sync local cache
  --exclude='storage/logs/'
  --exclude='storage/framework/cache/'
  --exclude='storage/framework/sessions/'
  --exclude='storage/framework/views/'
  --exclude='storage/app/'              # protect uploaded files on server
)

if [ "$INCLUDE_VENDOR" = "false" ]; then
  RSYNC_EXCLUDES+=(--exclude='vendor/')
  echo "   Excluding: node_modules/  resources/js/  vendor/  .env  storage runtime dirs"
else
  echo "   Excluding: node_modules/  resources/js/  .env  storage runtime dirs"
  echo "   Including: vendor/"
fi

rsync -az --progress \
  "${RSYNC_EXCLUDES[@]}" \
  -e "ssh -p $SSH_PORT" \
  "$SCRIPT_DIR/" \
  "$SSH_USER@$SSH_HOST:~/digitizing_workflow/"

ok "Files synced"

# ================================================================
# STEP 3 — Upload .env template (used only on fresh deploy)
# ================================================================
step "3 / 4  Uploading .env template"

cat > "$ENV_TMP" << EOF
APP_NAME="Digitizing Workflow"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=$APP_URL

FORCED_TENANT_ID=1

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=$DB_HOST
DB_PORT=3306
DB_DATABASE=$DB_DATABASE
DB_USERNAME=$DB_USERNAME
DB_PASSWORD=$DB_PASSWORD

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="$MAIL_FROM_ADDRESS"
MAIL_FROM_NAME="Digitizing Workflow"

VITE_APP_NAME="Digitizing Workflow"
EOF

scp -P "$SSH_PORT" "$ENV_TMP" "$SSH_USER@$SSH_HOST:~/.digitizing_env_production"
rm -f "$ENV_TMP"
ok "Template uploaded"

# ================================================================
# STEP 4 — Remote setup
# ================================================================
step "4 / 4  Running remote setup on server"

# NOTE: Unquoted heredoc — local variables ($INCLUDE_VENDOR etc.) expand here.
# Remote shell variables use \$ to avoid local expansion.
ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" /bin/bash << ENDSSH
set -euo pipefail

APP_DIR="\$HOME/digitizing_workflow"
PUBLIC_DIR="\$HOME/$PUBLIC_DIR"
ENV_STAGING="\$HOME/.digitizing_env_production"
INCLUDE_VENDOR="$INCLUDE_VENDOR"

echo ""

# ── Ensure required directories exist and are writable ──────────
mkdir -p "\$APP_DIR/bootstrap/cache"
mkdir -p "\$APP_DIR/storage/framework/views"
mkdir -p "\$APP_DIR/storage/framework/cache/data"
mkdir -p "\$APP_DIR/storage/framework/sessions"
mkdir -p "\$APP_DIR/storage/logs"
chmod -R 775 "\$APP_DIR/bootstrap/cache"
chmod -R 775 "\$APP_DIR/storage"

# ── Detect fresh vs update deploy ───────────────────────────────
# Fresh if .env is missing (new server or partial previous run)
FRESH_DEPLOY="false"
if [ ! -f "\$APP_DIR/.env" ]; then
  FRESH_DEPLOY="true"
  echo "   → Fresh deployment"
else
  echo "   → Update deployment"
fi

# ── Copy public folder to public_html ───────────────────────────
echo "   [1/6] Updating public_html..."
cp -r "\$APP_DIR/public/." "\$PUBLIC_DIR/"

# ── Patch index.php to point at the new app root ─────────────────
# Changes: __DIR__.'/../<folder>  →  __DIR__.'/../digitizing_workflow/<folder>
# Only patches if not already patched (idempotent)
echo "   [2/6] Patching public_html/index.php..."
if ! grep -q "digitizing_workflow" "\$PUBLIC_DIR/index.php"; then
  sed -i "s|'/../storage/|'/../digitizing_workflow/storage/|g"     "\$PUBLIC_DIR/index.php"
  sed -i "s|'/../vendor/|'/../digitizing_workflow/vendor/|g"       "\$PUBLIC_DIR/index.php"
  sed -i "s|'/../bootstrap/|'/../digitizing_workflow/bootstrap/|g" "\$PUBLIC_DIR/index.php"
  echo "      Paths patched"
else
  echo "      Already patched, skipping"
fi

# ── Composer ─────────────────────────────────────────────────────
cd "\$APP_DIR"
COMPOSER_CMD=\$(command -v composer 2>/dev/null || echo /usr/local/bin/composer)
if [ "\$INCLUDE_VENDOR" = "true" ]; then
  echo "   [3/6] Running composer dump-autoload (vendor synced)..."
  "\$COMPOSER_CMD" dump-autoload --no-dev --optimize --no-interaction
elif [ ! -d "\$APP_DIR/vendor" ]; then
  echo "   [3/6] Installing Composer dependencies (first deploy)..."
  "\$COMPOSER_CMD" install --no-dev --optimize-autoloader --no-interaction
else
  echo "   [3/6] Skipping Composer (no dependency changes)"
fi

# ── Set up .env (fresh deploy only) ─────────────────────────────
if [ "\$FRESH_DEPLOY" = "true" ]; then
  echo "   [4/6] Setting up .env and generating app key..."
  mv "\$ENV_STAGING" "\$APP_DIR/.env"
  php artisan key:generate --no-interaction
else
  echo "   [4/6] Keeping existing .env"
  rm -f "\$ENV_STAGING"
fi

# ── Run migrations ───────────────────────────────────────────────
echo "   [5/6] Running database migrations..."
php artisan migrate --force --no-interaction

# ── Permissions, storage symlink, optimise ───────────────────────
echo "   [6/6] Permissions, storage symlink, optimising..."
chmod -R 755 "\$APP_DIR"
chmod -R 775 "\$APP_DIR/storage"
chmod -R 775 "\$APP_DIR/bootstrap/cache"
chmod 640    "\$APP_DIR/.env"

rm -f "\$PUBLIC_DIR/storage"
ln -s "\$APP_DIR/storage/app/public" "\$PUBLIC_DIR/storage"

php artisan optimize:clear
php artisan package:discover --ansi
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "   Remote setup complete!"
ENDSSH

ok "Remote setup done"

echo ""
echo -e "${BOLD}${GREEN}╔══════════════════════════════════════════╗${NC}"
echo -e "${BOLD}${GREEN}║          Deployment Complete!            ║${NC}"
echo -e "${BOLD}${GREEN}╚══════════════════════════════════════════╝${NC}"
echo -e "   Visit  : ${BOLD}$APP_URL${NC}"
echo ""
echo -e "   ${YELLOW}First deploy?${NC} SSH in and update mail settings:"
echo -e "   ${YELLOW}ssh -p $SSH_PORT $SSH_USER@$SSH_HOST${NC}"
echo -e "   ${YELLOW}nano ~/digitizing_workflow/.env${NC}"
echo ""
echo -e "   Logs: ${YELLOW}tail -50 ~/digitizing_workflow/storage/logs/laravel.log${NC}"
echo ""
