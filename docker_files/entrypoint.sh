#!/bin/bash

mkdir /var/www/shot/files
chown www-data /var/www/shot/files

exec "$@"