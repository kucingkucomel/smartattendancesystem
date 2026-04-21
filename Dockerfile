FROM php:8.4-cli

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions pdo_mysql

WORKDIR /app
COPY . /app

CMD php -S 0.0.0.0:8080 -t /app
