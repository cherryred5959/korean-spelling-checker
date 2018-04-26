FROM php:7.2.1-cli

ENV php_version=7.2.1
ENV composer_version=1.6.2
ENV COMPOSER_ALLOW_SUPERUSER 1

RUN tar x --file=/usr/src/php.tar.xz --directory=/usr/src/  php-$php_version/php.ini-production php-$php_version/php.ini-development
RUN cp /usr/src/php-$php_version/php.ini-development /usr/local/etc/php/php.ini

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update
RUN apt-get install apt-utils -y --no-install-recommends
RUN apt-get install git -y

RUN apt-get install zlib1g-dev -y
RUN docker-php-ext-install zip
RUN apt-get update

RUN docker-php-ext-install pdo_mysql
RUN apt-get update

RUN apt-get install libcurl4-gnutls-dev

RUN curl -sS https://getcomposer.org/installer | php -- --version=$composer_version --install-dir=/usr/local/bin --filename=composer
RUN apt-get update

RUN composer global require hirak/prestissimo

WORKDIR /var/www/html
