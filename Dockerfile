FROM roquie/composer-parallel

COPY . /app

RUN composer install --no-ansi --no-interaction --no-progress --no-scripts --ignore-platform-reqs \
    && vendor/bin/box compile

FROM roquie/docker-swoole-webapp:php7.3-latest-brotli

COPY --from=0 /app/bin/server.phar /usr/bin/server
COPY --from=0 /app/dist /app
COPY --from=0 /app/configuration /app/configuration

RUN chmod +x /usr/bin/server

EXPOSE 8080
WORKDIR /app

ENTRYPOINT ["server"]
CMD ["run"]
