#!/usr/bin/env sh
set -eu

# Ensure .env exists
if [ ! -f .env ] && [ -f .env.production ]; then
  cp .env.production .env
fi

# Generate key if missing
if ! grep -q "APP_KEY=" .env || [ -z "$(grep APP_KEY .env | cut -d '=' -f2)" ]; then
  php artisan key:generate
fi

php artisan storage:link || true

php artisan config:clear
php artisan config:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force
fi

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"