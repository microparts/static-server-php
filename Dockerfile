FROM roquie/composer-parallel

COPY . /app

RUN composer install --no-ansi --no-interaction --no-progress --no-scripts --ignore-platform-reqs \
    && vendor/bin/box compile

FROM php:7.4-cli-alpine

RUN set -xe \
    apk update --no-cache \
    && apk add --no-cache ca-certificates nginx \
    && docker-php-ext-install pcntl \
    && mkdir /run/nginx

COPY --from=0 /app/bin/server.phar /usr/bin/server
COPY --from=0 /app/dist /app
COPY --from=0 /app/configuration /app/configuration

RUN chmod +x /usr/bin/server

EXPOSE 8080
WORKDIR /app

ENTRYPOINT ["server"]
CMD ["run"]
