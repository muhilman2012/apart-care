#!/usr/bin/env sh
set -e
if [ ! -f vendor/autoload.php ]; then composer install --no-interaction --prefer-dist; fi
php artisan storage:link --force >/dev/null 2>&1 || true
exec "$@"
