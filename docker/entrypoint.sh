#!/bin/bash

set -e -u

[[ $USERID ]] && usermod --uid "${USERID}" www-data && chown -R www-data:www-data /var/www/html

exec "$@"