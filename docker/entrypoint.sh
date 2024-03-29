#!/bin/bash

set -ex

php artisan config:clear --no-interaction

appKey=$(php artisan tinker --execute="echo config('app.key')")

if [ -z "$appKey" ]; then
  php artisan key:generate --force
fi

php artisan route:cache --no-interaction
php artisan event:cache --no-interaction
php artisan config:cache --no-interaction
php artisan view:cache --no-interaction

databaseHost=$(php artisan tinker --execute="echo config('database.connections.mysql.host')")
databasePort=$(php artisan tinker --execute="echo config('database.connections.mysql.port')")

wait-for-it $databaseHost:$databasePort -t 90 -- php artisan migrate --force

supervisord --configuration docker/supervisord.conf

echo "running docker-php-entrypoint with arguments $@"
docker-php-entrypoint $@
