#!/usr/bin/env sh
set -eu

if [ ! -f .env ] && [ -f .env.production ]; then
  cp .env.production .env
fi

php artisan storage:link || true

php artisan config:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force
fi

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
