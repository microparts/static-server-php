<?php declare(strict_types=1);

use StaticServer\Server;

require_once __DIR__ . '/vendor/autoload.php';

// Server read the following environment variables:
// CONFIG_PATH – server and frontend configuration.
// STAGE – server and frontend mode to start: prod/dev/local
// VCS_SHA1 – build commit sha1 for debug
// LOG_LEVEL – level of logging. Important! For swoole server, log_level needs to be set up in the `server.yaml` configuration file.

Server::new()->run();
