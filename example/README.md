# Example how to usage server with Docker

1. Develop an application or use an existing one. For example we use simple
boilerplate created from `vue create vue-app` command.
2. Build you web app with commands:
```bash
yarn install
yarn run build
```
3. Create `Dockerfile` inside root of project with following content
```Dockerfile
FROM microparts/static-server-php:1.0.0

COPY dist/ /app
COPY ./configuration /app/configuration

ARG VCS_SHA1
```

4. Build Docker-image use command (from root of project):
```bash
docker build -t vue-app:latest . --build-arg VCS_SHA1=$(git show -s --format=%h)
```

5. Run it
```bash
docker run --rm -it --init -p 8088:8080 vue-app:latest
```

6. Open the browser with app: http://localhost:8088
