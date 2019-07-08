# Example how to usage server with Docker

1. Develop an application or use an existing one. For example we use simple
boilerplate created from `vue create vue-app` command.
2. Create `Dockerfile` inside root of project with following content
```Dockerfile
FROM node:11-stretch
WORKDIR /usr/app
COPY . /usr/app

ARG STAGE

RUN npm ci --silent \
    && npm run build

FROM microparts/static-server-php:1.1.4

COPY --from=0 /usr/app/dist /app
COPY --from=0 /usr/app/configuration /app/configuration

ARG VCS_SHA1
ARG STAGE
ENV STAGE $STAGE
ENV VCS_SHA1 $VCS_SHA1
```

3. Build Docker-image use command (from root of project):
```bash
docker build -t vue-app:latest . --build-arg VCS_SHA1=$(git show -s --format=%h) --build-arg STAGE=dev
```

4. Run it
```bash
docker run --rm -it --init -p 8088:8080 vue-app:latest
```

5. Open the browser with app: http://localhost:8088
