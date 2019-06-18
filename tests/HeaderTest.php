<?php declare(strict_types=1);

namespace StaticServer\Tests;

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
            ->expects($this->exactly(count($conf->get('server.headers')) + 2))
            ->method('header')
            ->willReturn(null);

        $h = new Header($conf);
        $h->sent($response);

        $this->assertTrue(true);
    }
}
