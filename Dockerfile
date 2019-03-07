FROM php:7.1-apache
ENV LANG C.UTF-8
ENV APP_HOME /yaronet
RUN mkdir $APP_HOME || true
WORKDIR $APP_HOME
ADD package.json $APP_HOME

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libmcrypt-dev \
        gnupg \
    && curl -sL https://deb.nodesource.com/setup_10.x | bash - \
    && apt-get install -y \
        nodejs \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd mysqli mcrypt \
#    && printf "\n" | pecl install mcrypt-1.0.2 \
    && docker-php-ext-enable mcrypt \
    && npm install

ADD . $APP_HOME
RUN rm -rf /var/www/html && ln -s /yaronet/src /var/www/html
RUN cd /etc/apache2/mods-available/ && a2enmod rewrite
RUN chown -R www-data:www-data $APP_HOME

expose 80

ENTRYPOINT ["sh", "./setup/vm/docker-entrypoint.sh"]

