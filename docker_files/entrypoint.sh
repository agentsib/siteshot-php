#!/bin/bash

#mkdir /var/www/shot/cache
chown www-data /var/www/shot/cache

exec "$@"