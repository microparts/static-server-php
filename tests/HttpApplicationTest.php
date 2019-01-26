<?php declare(strict_types=1);

/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 * Date: 2019-01-19
 */

namespace StaticServer\Tests;

use StaticServer\SimpleInit;
use Throwable;

class HttpApplicationTest extends TestCase
{
    public function testInit()
    {
        try {
            SimpleInit::silent()->run(true);
        } catch (Throwable $e) {
            $this->assertFalse((bool) $e);
        }

        // workaround for simple check of application startup.
        $this->assertTrue(true);
    }
}
