#!/bin/bash

# Mount application folder to Apache default location.
# This avoids creating and configuring a new Apache location.
if [ -d /var/www/html ]; then
  cd /
  rm -rf /var/www/html
fi
ln -s ${APP_HOME}/src /var/www/html

# Install dependencies
cd ${APP_HOME}
npm install
/bin/sh setup/configure.sh

# Initialize database
php setup/docker/initdb.php

# Start Apache service
chown -R www-data:www-data src
if [ -f /var/run/apache2/apache2.pid ]; then
  rm /var/run/apache2/apache2.pid
fi
/usr/sbin/apache2 -DFOREGROUND
