<?php declare(strict_types=1);

namespace StaticServer\Tests\Handler;

use InvalidArgumentException;
use StaticServer\Handler\HandlerFactory;
use StaticServer\Handler\NginxHandler;
use StaticServer\Tests\TestCase;

class HandlerFactoryTest extends TestCase
{
    public function testHowFactoryCreatesObjects()
    {
        $object = HandlerFactory::createHandler('nginx', ['pid' => '', 'config' => '']);

        $this->assertInstanceOf(NginxHandler::class, $object);
    }

    public function testHowFactoryHandleTheInvalidParams()
    {
        $this->expectException(InvalidArgumentException::class);
        HandlerFactory::createHandler('random', []);
    }
}
