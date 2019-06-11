<?php declare(strict_types=1);

namespace StaticServer\Tests;

use StaticServer\Server;
use Throwable;

class HttpApplicationTest extends TestCase
{
    public function testInit()
    {
        try {
            Server::silent()->run(true);
        } catch (Throwable $e) {
            printf($e);
            $this->assertFalse((bool) $e);
        }

        // workaround for simple check of application startup.
        $this->assertTrue(true);
    }
}
