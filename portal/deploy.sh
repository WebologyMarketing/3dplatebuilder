#!/bin/sh
# Run by Plesk after each deploy of app.3dplatebuilder.com:  sh portal/deploy.sh
# Finds Plesk's own PHP (8.3 or newer) and Composer, so the deploy action needs nothing else.
# Override with PHP_BIN=/path/to/php if needed.
set -e
cd "$(dirname "$0")"

PHP="${PHP_BIN:-}"
if [ -z "$PHP" ]; then
  for v in 8.5 8.4 8.3; do
    if [ -x "/opt/plesk/php/$v/bin/php" ]; then PHP="/opt/plesk/php/$v/bin/php"; break; fi
  done
fi
PHP="${PHP:-php}"
echo "Using PHP: $PHP ($($PHP -r 'echo PHP_VERSION;'))"

# Composer: Plesk's copy if there is one, else a copy kept in portal/ (not in Git).
COMPOSER_PHAR=""
for c in /usr/lib/plesk-9.0/composer.phar /usr/lib64/plesk-9.0/composer.phar /usr/local/psa/var/modules/composer/composer.phar ./composer.phar; do
  if [ -f "$c" ]; then COMPOSER_PHAR="$c"; break; fi
done
if [ -z "$COMPOSER_PHAR" ]; then
  echo "Fetching Composer..."
  $PHP -r "copy('https://getcomposer.org/download/latest-stable/composer.phar', 'composer.phar');"
  COMPOSER_PHAR=./composer.phar
fi

$PHP "$COMPOSER_PHAR" install --no-dev --optimize-autoloader --no-interaction --no-progress

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
