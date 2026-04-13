#!/usr/bin/env sh
set -eu

php artisan storage:link || true

php artisan config:clear
php artisan config:cache
php artisan cache:clear

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force
fi

exec php -S 0.0.0.0:${PORT:-10000} -t public