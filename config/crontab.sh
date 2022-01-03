#!/usr/bin/env bash

su -p -s /bin/bash -c ". /env; drush --root=/var/www/html/web core-cron > /var/log/console 2>&1" www-data
