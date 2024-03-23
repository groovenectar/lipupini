FROM php:8.3.4-alpine3.19

RUN apk add --no-cache composer tini
# Development libraries required by PHP extensions
RUN apk add --no-cache zlib-dev libpng-dev libjpeg-turbo-dev curl-dev
# Now we can install the extensions
RUN docker-php-ext-install gd curl

COPY . /app/
VOLUME /app/collection

RUN cd /app/module/Lipupini && composer install --no-interaction --no-dev --prefer-dist

ENTRYPOINT ["tini", "--"]
CMD ["ash", "-c", "cd /app/module/Lukinview/webroot && PHP_CLI_SERVER_WORKERS=2 php -S 0.0.0.0:4000 index.php"]

WORKDIR /app
EXPOSE 4000
