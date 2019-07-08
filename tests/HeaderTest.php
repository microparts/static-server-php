<?php declare(strict_types=1);

namespace StaticServer\Tests;

use InvalidArgumentException;
use Microparts\Configuration\Configuration;
use StaticServer\Header;
use Swoole\Http\Response;

class HeaderTest extends TestCase
{
    public function testSendingHeaders()
    {
        $conf = new Configuration(__DIR__ . '/configuration', 'nested');
        $conf->load();

        $response = $this->createMock(Response::class);
        $response
            ->expects($this->exactly(count($conf->get('server.headers')) + 3)) // +3 because 2 hardcoded and 1 header have 2 values.
            ->method('header')
            ->willReturn(null);

        $h = new Header($conf);
        $h->sent($response);

        $this->assertTrue(true);
    }

    public function testHowInvalidHeaderNameCheckWorks()
    {
        $conf = new Configuration(__DIR__ . '/configuration', 'invalid_header_name');
        $conf->load();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header not supported.');

        $response = $this->createMock(Response::class);
        $response
            ->method('header')
            ->willReturn(null);

        $h = new Header($conf);
        $h->sent($response);

        $this->assertTrue(true);
    }

    public function testHowInvalidHeaderValueCheckWorks()
    {
        $conf = new Configuration(__DIR__ . '/configuration', 'invalid_header_value');
        $conf->load();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid header format, see docs & examples.');

        $response = $this->createMock(Response::class);
        $response
            ->method('header')
            ->willReturn(null);

        $h = new Header($conf);
        $h->sent($response);

        $this->assertTrue(true);
    }
}
