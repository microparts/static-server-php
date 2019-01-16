PHP Microservice configuration module
-------------------------------------

[![CircleCI](https://circleci.com/gh/microparts/configuration-php/tree/master.svg?style=svg)](https://circleci.com/gh/microparts/configuration-php/tree/master)

Configuration module for microservices written on PHP. Specially created
for follow up corporate standards of application configuration.

## Installation

```bash
composer install microparts/configuration-php
```

## Usage

By default path to configuration directory and application stage
loading from `/app/configuration` with `local` stage.

1) Simple
```php
use Microparts\Configuration\Configuration;

$conf = new Configuration();
$conf->load();

var_dump($conf->all()); // get all config
echo $conf->get('foo.bar'); // get nested key use dot notation
echo $conf['foo.bar']; // thie same, but use ArrayAccess interface.
```

2) If u would like override default values, you can pass 2 arguments to
class constructor or set up use setters.

```php
use Microparts\Configuration\Configuration;

$conf = new Configuration(__DIR__ . '/configuration', 'test');
$conf->load();

$conf->get('key'); // full example on the top
```

3) If the operating system has an env variables `CONFIG_PATH` and `STAGE`,
then values for the package will be taken from there.

```bash
export CONFIG_PATH=/configuration
export STAGE=prod
```

```php
use Microparts\Configuration\Configuration;

$conf = new Configuration();
$conf->load(); // loaded files from /configuration for prod stage.

$conf->get('key'); // full example on the top
```

4) If u want to see logs and see how load process working,
pass you application logger to the following method:

```php
use Microparts\Configuration\Configuration;

$conf = new Configuration();
$conf->setLogger($monolog); // PSR compatible logger.
$conf->load();

$conf->get('key'); // full example on the top
```

That all.

## Depends

* \>= PHP 7.1
* Composer for install package

## License

GNU GPL v3
