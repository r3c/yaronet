FROM php:7.1-apache
# ENV LANG C.UTF-8
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libmcrypt-dev \
        gnupg \
        locales \
    && curl -sL https://deb.nodesource.com/setup_10.x | bash - \
    && apt-get install -y \
        nodejs \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd mysqli mcrypt \
#    && printf "\n" | pecl install mcrypt-1.0.2 \
    && docker-php-ext-enable mcrypt
RUN sed -ri 's/# (en_US.UTF-8 UTF-8)/\1/' /etc/locale.gen \
    && locale-gen
RUN cd /etc/apache2/mods-available/ && a2enmod rewrite

EXPOSE 80
