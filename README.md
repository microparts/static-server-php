Static server
-------------

[![CircleCI](https://circleci.com/gh/microparts/static-server-php/tree/master.svg?style=svg)](https://circleci.com/gh/microparts/static-server-php/tree/master)
[![codecov](https://codecov.io/gh/microparts/static-server-php/branch/master/graph/badge.svg)](https://codecov.io/gh/microparts/static-server-php)


Special static server with support corporate standard of configuration and more.
It has the similar performance compare with the NGINX static files server.

Server created for javascript SPA apps like: Vue, React, Angular, etc.

## Features

* Incredibly fast. Server hits more than [182k request per second](./benchmark).
* Special created for modern web app's.
* Secure headers by default.
* If backend app will be hacked, the hacker may write a letter to us, because email address injected to head section of index (console message) :)
* Corporate config standard supported by default and injected too.
* Brotli-compression. Compression based on `Accept-Encoding` header. [More](#Compression).
* Deny all `robots.txt` by default.

## Docker usage

```Dockerfile
FROM microparts/static-server-php:1.1.0

COPY dist/ /app
# frontend yaml configuration
COPY ./configuration /app/configuration

ARG VCS_SHA1
ARG STAGE
ENV STAGE $STAGE
ENV VCS_SHA1 $VCS_SHA1
```

Full example can be founded [here](./example). And with local use [here](https://github.com/microparts/configuration-js#how-to-usage-library-with-spa-apps).

## CLI usage

CLI usage implies 2 commands for usage:

1) Start server:
```bash
bin/static-server run
```

Result:
```bash
[2019-06-19 08:54:52] Server.INFO: CONFIG_PATH = /app/configuration  
[2019-06-19 08:54:52] Server.INFO: STAGE = dev    
[2019-06-19 08:54:52] Server.INFO: Configuration loaded.  
[2019-06-19 08:54:52] Server.INFO: Application state is: STAGE=dev SHA1=55b5293  
[2019-06-19 08:54:52] Server.INFO: HTTP static server started at 0.0.0.0:8080 
```


2) Dump loaded configuration:
```bash
bin/static-server dump
```

Result:
```bash
CONFIG_PATH = /app/configuration
STAGE = dev
VCS_SHA1 = 55b5293
LOG_LEVEL = info

server:
  compression:
    enabled: true
    fallback: gzip
    algorithms:
      - level: 11
        method: br
      - level: 7
        method: gzip
      - level: 7
        method: deflate
    extensions:
      - js
      - xml
      - map
      - json
      - svg
      - mjs
      - html
      - htm
      - md
      - css
      - txt
      - csv
      - woff
      - woff2
  host: 0.0.0.0
  port: 8080
  root: /app
  index: index.html
  log_info: '%%cSTAGE=%s SHA1=%s; %%cSecurity bugs: security@teamc.io, Job/partnership: work@teamc.io'
  config:
    inject: before_script
  swoole:
    worker_num: 4
    log_level: 0
    buffer_output_size: 33554432
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
  headers:
    csp:
      - default-src 'self'
      - script-src 'self' 'unsafe-inline' cdnjs.cloudflare.com
      - img-src 'self' data:
      - style-src 'self' 'unsafe-inline' fonts.googleapis.com cdnjs.cloudflare.com
      - font-src 'self' data: fonts.gstatic.com cdnjs.cloudflare.com
      - form-action 'self'
    feature_policy:
      - geolocation 'none'
      - payment 'none'
      - microphone 'none'
      - camera 'none'
      - autoplay 'none'
    referer_policy: no-referrer
    pragma: public
    cache_control: public, must-revalidate, proxy-revalidate, max-age=31536000
    frame_options: sameorigin
    xss_protection: 1; mode=block
    x_content_type: nosniff
    x_content_type_options: nosniff
    x_ua_compatible: IE=edge
    sts: 'max-age=31536000; includeSubDomains; preload'
```

Comments about server configuration can be found [here](./configuration/defaults).

## How it works?

1. Server reads files from `dist`, then modifying `index.html` on the fly and append configuration before first `<script>` tag will be founded.
 Also available [insert config before first tag](./configuration/defaults/___server.yaml#L8) to `<head>` section (but it blocks page painting).

Injected config file (`__config.js`) has following content:

```js
window.__stage = 'local';
window.__config = JSON.parse('{}' /* frontend config from yaml here */);
window.__vcs = '%s';

console.log('%%cSTAGE=dev SHA1=55b5293; %%cSecurity bugs: security@teamc.io, Job/partnership: work@teamc.io','color:#F44336','color:#009688');
```

3. Loads all content of static files to memory
4. Starts the server.

## Headers

By default will be added following headers to response:
```http
Pragma: public
Cache-Control: public, must-revalidate, proxy-revalidate, max-age=31536000
X-XSS-Protection: 1; mode=block
X-Frame-Options: SAMEORIGIN
X-Content-Type: nosniff
X-Content-Type-Options: nosniff
X-Ua-Compatible: IE=edge
Referrer-Policy: no-referrer
Feature-Policy: geolocation 'none'; payment 'none'; microphone 'none'; camera 'none'; autoplay 'none'
Content-Security-Policy: default-src 'self'; script-src 'self' cdnjs.cloudflare.com; img-src 'self' data:; style-src 'self' 'unsafe-inline' fonts.googleapis.com cdnjs.cloudflare.com; font-src 'self' data: fonts.gstatic.com cdnjs.cloudflare.com; form-action 'self'
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
```

`A+` rating issued by the site https://securityheaders.com.

<img alt="Secure headers proof" src="./resource/secureheaderscom.png" height="514" />
<br>

Also, a frontend-developer should be use `Content Security Policy` protection, i.e configure server himself.

It creates a `server.yaml` config file and add it to stage directory (dev/prod/local) with following contents:

```yaml
dev:
  server:
    headers:
      csp:
        - default-src 'self' *.teamcsrv.com *.teamc.io
        - script-src 'self'
        - "img-src 'self' data:"
        - style-src 'self' 'unsafe-inline' fonts.googleapis.com
        - "font-src 'self' data: fonts.gstatic.com"
        - form-action 'self'
```

And it edit in accordance with business logic of application.

## Compression

By default server use Brotli-compression algorithm developed by [Google Inc](https://en.wikipedia.org/wiki/Brotli). <br>
If a more [effective](https://medium.com/oyotech/how-brotli-compression-gave-us-37-latency-improvement-14d41e50fee4) (up to 21%) lossless compression algorithm than gzip and deflate.<br>
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

```
PHPUnit 8.2.2 by Sebastian Bergmann and contributors.

Runtime:       PHP 7.3.2 with Xdebug 2.7.2
Configuration: /Users/roquie/projects/microparts/static-server-php/phpunit.xml

................................................                  48 / 48 (100%)

Time: 474 ms, Memory: 10.00 MB

OK (48 tests, 92 assertions)
```

## License

GNU GPL v3
