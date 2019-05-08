#!/bin/bash
set -e

grunt
rm -rf var/cache/prod/*
bin/console cache:clear --env=prod --no-warmup
bin/console cache:warmup --env=prod
chown -R www-data:www-data var

exec "$@"