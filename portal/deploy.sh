#!/bin/sh
# Run by Plesk after each deploy of app.3dplatebuilder.com:  sh portal/deploy.sh
# PHP and COMPOSER can be set by the caller, for Plesk's own PHP 8.3 binary.
set -e
cd "$(dirname "$0")"
PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"

$COMPOSER install --no-dev --optimize-autoloader --no-interaction --no-progress

if [ ! -f .env ]; then
  echo "portal/.env is missing: copy .env.example to .env on the server and fill it in, then deploy again."
  exit 0
fi

grep -q '^APP_KEY=base64:' .env || $PHP artisan key:generate --force
$PHP artisan migrate --force
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
$PHP artisan portal:make-admin --if-missing --email="${ADMIN_EMAIL:-hello@3dplatebuilder.com}" --name="${ADMIN_NAME:-Harsh}"
echo "Portal deployed."
