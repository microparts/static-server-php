Static server
-------------

[![CircleCI](https://circleci.com/gh/microparts/static-server-php/tree/master.svg?style=svg)](https://circleci.com/gh/microparts/static-server-php/tree/master)

Special static server with support corporate standard of configuration and more.
It has the similar performance compare with the Nginx static files server.

Server created for javascript SPA apps like: Vue, React, Angular, etc.

## Features

* Incredibly fast. Server hits more than [182k request per second](./benchmark).
* Special created for modern web app's.
* Secure headers by default.
* If backend app will be hacked, the hacker may write a letter to us, because email address injected to head section of index (console message) :)
* Corporate config standard supported by default and injected too.
* Brotli-compression by default.
* Deny all `robots.txt` by default.

## Docker usage

```Dockerfile
FROM microparts/static-server-php:1.0.0

COPY dist/ /app
COPY ./configuration /app/configuration

ARG VCS_SHA1
ARG STAGE
ENV STAGE $STAGE
ENV VCS_SHA1 $VCS_SHA1
```

Full example can be founded [here](./example).

## CLI usage

CLI usage implies 2 commands for usage:

1) Start server:
```bash
bin/static-server run
```

Result:
```bash
[2019-01-19 16:14:18] Server.INFO: CONFIG_PATH = ./configuration
[2019-01-19 16:14:18] Server.INFO: STAGE = local
[2019-01-19 16:14:18] Server.INFO: Configuration module loaded
[2019-01-19 16:14:18] Server.INFO: HTTP static server started at 0.0.0.0:8080
```


2) Dump loaded configuration:
```bash
bin/static-server dump
```

Result:
```bash
CONFIG_PATH = ./configuration
STAGE = local
VCS_SHA1 =
LOG_LEVEL = info

server:
  host: 0.0.0.0
  port: 8080
  root: ./dist
  index: index.html
  swoole:
    log_level: 3
    http_compression: true
  log_info:
    security: '%cDo you have a security note for this site? Please write a letter to us: %csecurity@teamc.io'
    job: '%cJob offer or partnership: %cwork@teamc.io'
  headers:
    csp:
      - 'default-src ''self'''
      - 'script-src ''self'' cdnjs.cloudflare.com'
      - 'img-src ''self'' data:'
      - 'style-src ''self'' ''unsafe-inline'' fonts.googleapis.com cdnjs.cloudflare.com'
      - 'font-src ''self'' data: fonts.gstatic.com cdnjs.cloudflare.com'
      - 'form-action ''self'''
    feature_policy:
      - 'geolocation ''none'''
      - 'payment ''none'''
      - 'microphone ''none'''
      - 'camera ''none'''
      - 'autoplay ''none'''
    referer_policy: no-referrer
  mimes:
    map: application/json
    xml: application/xml
    json: application/json
    txt: text/plain
    html: text/html
    htm: text/html
    md: text/plain
    css: text/css
    js: text/javascript
    mjs: text/javascript
    png: image/png
    gif: image/gif
    jpg: image/jpg
    jpeg: image/jpg
    svg: image/svg+xml
    webp: image/webp
    bmp: image/bmp
    ico: image/x-icon
    tif: image/tiff
    tiff: image/tiff
    ts: application/typescript
    otf: application/x-font-opentype
    ttf: font/ttf
    woff: font/woff
    woff2: font/woff2
    eot: application/vnd.ms-fontobject
    sfnt: application/font-sfnt
    csv: text/csv
```

## How it works?

1. Read files from `dist` with modifying `index.html` on the fly and append
configuration with VCS SHA1 to `<head>` section. Like this:

```html
<html lang="en">
  <head>
    <script>
      window.__stage = 'local';
      window.__config = JSON.parse('{}');
      window.__vcs = '55b5293';

      console.log('%cDo you have a security note for this site? Please write a letter to us: %csecurity@teamc.io', 'color: #009688', 'color: #F44336');
      console.log('%cJob offer or partnership: %cwork@teamc.io', 'color: #009688', 'color: #F44336');
    </script>
    <meta charset="utf-8">
<!-- ... -->
```
3. Load all content of static files to memory
4. Start server.

## Headers

By default will be added following headers to response:
```http
Expires: 0
Pragma: public
Cache-Control: "public, must-revalidate, proxy-revalidate"
x-xss-protection: 1; mode=block
x-frame-options: SAMEORIGIN
x-content-type: nosniff
X-Content-Type-Options: nosniff
X-Ua-Compatible: IE=edge
Referrer-Policy: no-referrer
Feature-Policy: geolocation 'none';payment 'none';microphone 'none';camera 'none';autoplay 'none'
Content-Security-Policy: default-src 'self';script-src 'self' cdnjs.cloudflare.com;img-src 'self' data:;style-src 'self' 'unsafe-inline' fonts.googleapis.com cdnjs.cloudflare.com;font-src 'self' data: fonts.gstatic.com cdnjs.cloudflare.com;form-action 'self'
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
```

Also, frontend developer can be manually add `Content Security Policy` protection,
use HTML meta tag. [Read more](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy#Examples).

## Compression

By default server use Brotli-compression algorithm developed by [Google Inc](https://en.wikipedia.org/wiki/Brotli). <br>
If a more effective (up to 20%) lossless compression algorithm than gzip and deflate.<br>
<br>
For the present, his support all modern browsers:
https://caniuse.com/#search=Brotli

## Environment variables

Server read the following environment variables:

```bash
CONFIG_PATH – server and frontend configuration.
STAGE – server and frontend mode to start: prod/dev/local
VCS_SHA1 – build commit sha1 for debug
LOG_LEVEL – level of logging. Important! For swoole server, log_level needs to be set up in the `server.yaml` configuration file.
```

## Default files in the root directory

By default root directory is `/app`. It special for container-based usage. <br>
Root directory contains following files from scratch:
```
.
├── favicon.ico
├── index.html
└── robots.txt
```

* `favicon.ico` – is a transparent `.ico` file (for prevent error logs).
* `index.html` – simple index file with hello message.
* `robots.txt` – the file which blocks all robots by default.

Each file can be replaced.

## Tests

Install packages for development using composer and just run following command:

```
vendor/bin/phpunit
```

## License

GNU GPL v3
