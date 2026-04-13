#!/usr/bin/env sh
set -eu

# Create required Laravel writable folders
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

php artisan storage:link || true

php artisan config:clear
php artisan cache:clear
php artisan config:cache

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force
fi

if [ "${RUN_SEEDER:-false}" = "true" ]; then
    php artisan db:seed --force
fi

exec php -S 0.0.0.0:${PORT:-10000} -t public