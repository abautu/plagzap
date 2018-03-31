FROM php:5.6.33-apache

RUN set -ex \
    && usermod -u 1000 www-data \
    && groupmod -g 1000 www-data \
    && a2enmod rewrite \
    \
    && buildDeps=' \
        libjpeg62-turbo-dev \
        libpng12-dev \
        libicu-dev \
        libmcrypt-dev \
        libxml2-dev \
    ' \
    && apt-get update && apt-get install -y --no-install-recommends $buildDeps && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd \
        --with-jpeg-dir=/usr \
        --with-png-dir=/usr \
    && docker-php-ext-install -j "$(nproc)" gd intl mbstring mcrypt mysql mysqli pdo pdo_mysql xml zip \
    && apt-mark manual \
        libjpeg62-turbo \
        libicu52 \
        libmcrypt4 \
        libxml2 \
    && apt-get purge -y --auto-remove $buildDeps \
    \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    \
    && curl -fsSL 'https://xcache.lighttpd.net/pub/Releases/3.2.0/xcache-3.2.0.tar.gz' -o xcache.tar.gz \
    && mkdir -p /tmp/xcache \
    && tar -xf xcache.tar.gz -C /tmp/xcache --strip-components=1 \
    && rm xcache.tar.gz \
    && docker-php-ext-configure /tmp/xcache --enable-xcache \
    && docker-php-ext-install /tmp/xcache \
    && rm -r /tmp/xcache \
    \
    && ln -s /var/www/php.ini /usr/local/etc/php/php.ini

VOLUME /var/www/
WORKDIR /var/www/html
