<?php declare(strict_types=1);

namespace StaticServer\Tests\Processor;

use Microparts\Configuration\Configuration;
use StaticServer\Compression\CompressionFactory;
use StaticServer\Modifier\Modify;
use StaticServer\Modifier\NullModify;
use StaticServer\Processor\SpaProcessor;
use StaticServer\Tests\TestCase;
use Swoole\Http\Request;
use Swoole\Http\Response;

class SpaProcessorTest extends TestCase
{
    public function provideDataToServeFiles()
    {
        return [
            ['/foobar', file_get_contents(__FILE__)],
            ['/file.omg', '404 not found'],
            ['/', CompressionFactory::create('br', 11)->compress('')],
            ['/healthcheck', 'ok'],
            ['/asd', ''],
        ];
    }

    /**
     * @dataProvider provideDataToServeFiles
     * @param $uri
     * @param $expected
     */
    public function testHowToProcessorServeFiles($uri, $expected)
    {
        $m = new Modify();
        $m->addModifier(new NullModify());
        $m->addTemplate(__FILE__, '/foobar');

        $conf = new Configuration(__DIR__ . '/../configuration', 'nested');
        $conf->load();

        $spa = new SpaProcessor($conf);

        $spa->load($m->modify([]));

        $request = $this->createMock(Request::class);
        $request->server['request_uri'] = $uri;
        $request->header['accept-encoding'] = 'gzip, br';

        $response = $this->createMock(Response::class);
        $response
            ->expects($this->any())
            ->method('header')
            ->will($this->returnValueMap([
                ['Content-Encoding', 'br'],
                ['Content-Type', 'text/plain'],
            ]));

        $body = '';
        $spa->process($body, $request, $response);

        $this->assertEquals($expected, $body);
    }
}
