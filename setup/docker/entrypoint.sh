#!/bin/sh -e

# Mount application folder to Apache default location.
# This avoids creating and configuring a new Apache location.
if [ -d /var/www/html ]; then
	rm -rf /var/www/html
fi

ln -s "${APP_HOME}/src" /var/www/html

cd "${APP_HOME}"

# Install dependencies
chown -R www-data:www-data setup/module src /var/www
npm install
su -s setup/configure.sh www-data

# Initialize database
php setup/docker/initdb.php

# Start Apache service
if [ -f /var/run/apache2/apache2.pid ]; then
	rm /var/run/apache2/apache2.pid
fi

/usr/sbin/apache2 -DFOREGROUND
