FROM php:8.4-fpm-alpine

RUN apk add --no-cache bash curl libpng-dev libzip-dev oniguruma-dev icu-dev $PHPIZE_DEPS \
    && docker-php-ext-install bcmath intl mbstring pdo_mysql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY docker/php/entrypoint.sh /usr/local/bin/apart-care-entrypoint
RUN chmod +x /usr/local/bin/apart-care-entrypoint
ENTRYPOINT ["apart-care-entrypoint"]
CMD ["php-fpm"]
