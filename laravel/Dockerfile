# syntax=docker/dockerfile:1
FROM composer:2 AS build
WORKDIR /app
RUN --mount=type=secret,id=proxy_ca,required=false \
    if [ -f /run/secrets/proxy_ca ]; then export SSL_CERT_FILE=/run/secrets/proxy_ca; fi; docker-php-ext-install pdo_mysql
COPY composer.json composer.lock ./
RUN --mount=type=secret,id=proxy_ca,required=false \
    if [ -f /run/secrets/proxy_ca ]; then export COMPOSER_CAFILE=/run/secrets/proxy_ca; fi; composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-progress
COPY . .
RUN composer dump-autoload --no-dev --optimize && chmod -R a+rX /app && chown -R 33:33 storage bootstrap/cache
USER 33:33
EXPOSE 8080
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
