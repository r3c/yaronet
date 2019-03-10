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
chown -R www-data:www-data setup/module src /var/www
npm install
su -s setup/configure.sh www-data

# Initialize database
php setup/docker/initdb.php
if [ $? -ne "0" ]; then
  exit 1
fi

# Start Apache service
if [ -f /var/run/apache2/apache2.pid ]; then
  rm /var/run/apache2/apache2.pid
fi
/usr/sbin/apache2 -DFOREGROUND
