FROM roquie/composer-parallel

COPY . /app
RUN composer install --no-ansi --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader --ignore-platform-reqs

FROM roquie/docker-swoole-webapp

COPY --from=0 /app /app

EXPOSE 8080
WORKDIR /app

CMD ["sh", "-c", "php index.php"]
