FROM php:8.4-apache

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions pdo_mysql

COPY . /var/www/html/
