#!/usr/bin/env bash

apt-get update -y && apt-get upgrade -y

apt-get install build-essential libssl-dev git -y

git clone https://github.com/wg/wrk.git wrk
cd wrk
make
cp wrk /usr/local/bin
chmod +x /usr/local/bin/wrk

apt install php7.2-cli -y
apt install php-pear -y
apt install php7.2-dev -y
printf 'no\nno\nno\nno\nno' | pecl install swoole
echo 'extension=swoole.so' >> /etc/php/7.2/cli/php.ini

git clone https://github.com/microparts/static-server-php.git
cd static-server-php

php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '93b54496392c062774670ac18b134c3b3a95e5a5e5c8f1a9f115f203b75bf9a129d5daa8ba6a13e2cc8a1da0806388a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"

chmod +x composer.phar

./composer.phar install --no-dev

mkdir configuration/local
touch configuration/local/server.yaml

echo "
local:
  server:
    root: ./dist
" > configuration/local/server.yaml

echo
echo 'Complete.'
