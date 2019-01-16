<?php declare(strict_types=1);
/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 * Date: 2019-01-15
 */

use Microparts\Configuration\Configuration;
use Psr\Log\LogLevel;
use StaticServer\Handler\InjectConfigToIndexHandler;
use StaticServer\HttpApplication;
use StaticServer\Middleware\ContentSecurityPolicyMiddleware;
use StaticServer\PrettyLogger;
use StaticServer\FileWalker;

require_once __DIR__ . '/vendor/autoload.php';

$path  = getenv('CONFIG_PATH') ?: './configuration';
$stage = getenv('STAGE') ?: 'local';
$sha1  = getenv('VCS_SHA1') ?: '';
$level = getenv('LOG_LEVEL') ?: LogLevel::INFO;

$logger = PrettyLogger::create($level);
$conf = new Configuration($path, $stage);
$conf->setLogger($logger);
$conf->load();

$walker = new FileWalker($logger);
$walker->addHandler(new InjectConfigToIndexHandler($conf, $stage, $sha1));

$http = new HttpApplication($conf, $logger, $walker);
$http->use(new ContentSecurityPolicyMiddleware($conf));

$http->run();

