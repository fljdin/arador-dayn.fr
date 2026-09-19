#!/bin/sh
php-fpm --daemonize
nginx -g "daemon off;"
