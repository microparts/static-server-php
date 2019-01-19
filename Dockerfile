FROM roquie/composer-parallel

COPY . /app
RUN composer install --no-ansi --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader --ignore-platform-reqs

FROM roquie/docker-swoole-webapp:7.3-latest

COPY --from=0 /app /srv/server
COPY --from=0 /app/dist /app
COPY --from=0 /app/configuration /app/configuration

EXPOSE 8080
WORKDIR /app

ENTRYPOINT /srv/server/bin/static-server run
