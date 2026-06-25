#!/bin/sh
set -e

# Render the upload limits from the environment before starting PHP.
: "${UPLOAD_MAX_SIZE:=30M}"
export UPLOAD_MAX_SIZE
envsubst '${UPLOAD_MAX_SIZE}' \
    < /usr/local/etc/php/conf.d/uploads.ini.template \
    > /usr/local/etc/php/conf.d/uploads.ini

# Hand off to the stock PHP entrypoint (handles php-fpm and any other command).
exec docker-php-entrypoint "$@"
