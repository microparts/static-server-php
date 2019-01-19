Static server
-------------

Special static server with support corporate standard of configuration and more. <br>
**It has the similar performance compare with the Nginx static files server.**

Server created for javascript SPA apps like: Vue, React, Angular, etc.

## Usage

```Dockerfile
FROM microparts/static-server-php:latest

COPY dist/ /app

ARG VCS_SHA1
```

## How it works?

1. Read files from `dist` with modifying `index.html` on the fly and append
configuration with VCS SHA1 to `<head>` section. Like this:

```html
<html lang="en">
  <head>
    <script>
      window.__stage = 'local';
      window.__config = JSON.parse('{"content_security_policy":[]}');
      window.__vcs = '55b5293';

      console.log('%cDo you have a security note for this site? Please write a letter to us: %csecurity@teamc.io', 'color: #009688', 'color: #F44336');
      console.log('%cJob offer or partnership: %cwork@teamc.io', 'color: #009688', 'color: #F44336');
    </script>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
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
```

Also, available `Content Security Policy` header,
but developer should be written values to config manually.

## Tests

Install packages for development using composer and just run following command:

```
vendor/bin/phpunit
```

## License

GNU GPL v3
