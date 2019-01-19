<?php declare(strict_types=1);

/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 * Date: 2019-01-19
 */

namespace StaticServer\Tests;

use Microparts\Configuration\Configuration;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use StaticServer\FileWalker;
use StaticServer\Handler\InjectConfigToIndexHandler;
use StaticServer\HttpApplication;
use StaticServer\Middleware\ContentSecurityPolicyMiddleware;
use StaticServer\PrettyLogger;
use Throwable;

class HttpApplicationTest extends TestCase
{
    public function testInit()
    {
        try {
            $this->init();
        } catch (Throwable $e) {
            $this->assertFalse((bool) $e);
        }

        // workaround for simple check of application startup.
        $this->assertTrue(true);
    }

    private function init()
    {
        $path  = __DIR__ . '/configuration';
        $stage = 'local';
        $sha1  = '';

        $logger = new NullLogger();
        $conf = new Configuration($path, $stage);
        $conf->setLogger($logger);
        $conf->load();

        $walker = new FileWalker($logger);
        $walker->addHandler(new InjectConfigToIndexHandler($conf, $stage, $sha1));

        $http = new HttpApplication($conf, $logger, $walker);
        $http->use(new ContentSecurityPolicyMiddleware($conf));

        $http->dryRun();
    }
}
