<?php declare(strict_types=1);

namespace StaticServer\Tests\Compression;

use Microparts\Configuration\Configuration;
use StaticServer\Compression\Compress;
use StaticServer\Compression\CompressionFactory;
use StaticServer\Tests\TestCase;
use Swoole\Http\Request;
use Swoole\Http\Response;

class CompressTest extends TestCase
{
    /**
     * Cached files, mimes, extensions.
     *
     * $cached['mimes']['/js/app.js'] = 'application/javascript';
     *
     * @var array
     */
    private static $cached = [
        'mimes'     => [
            '/index.html' => 'text/plain'
        ],
        'files'     => [
            '/index.html' => 'hello world'
        ],
        'extension' => [
            '/index.html' => 'html'
        ],
    ];

    public function testCompressionIfItDisabledInConfig()
    {
        $conf = new Configuration(__DIR__ . '/../configuration', 'tests_compress_off');
        $conf->load();

        $c = new Compress($conf);

        $request = $this->createMock(Request::class);
        $request->server['request_uri'] = '/index.html';
        $request->header['accept-encoding'] = 'gzip, deflate, br';

        $response = $this->createMock(Response::class);

        $body = self::LOREM_IPSUM;
        $cached = self::$cached;
        $c->handle($body, $cached, $request, $response);

        $this->assertSame(self::LOREM_IPSUM, $body);
        $this->assertSame(self::$cached, $cached);
    }

    // https://tools.ietf.org/html/rfc7231#section-5.3.4
    public function provideAcceptEncodingHeadersToTestCompression()
    {
        return [
            ['br', 11, 'gzip, deflate, br'], // special duplicates to check cache
            ['br', 11, 'gzip, deflate, br'], // special duplicates to check cache
            ['br', 11, 'gzip, deflate, br'], // special duplicates to check cache
            ['br', 11, 'gzip, deflate, br'], // special duplicates to check cache
            ['br', 11, 'gzip,deflate,br'],
            ['br', 11, 'br'],
            ['gzip', 7, 'gzip'],
            ['deflate', 7, 'deflate'],
            ['gzip', 7, 'deflate, gzip'],
            ['gzip', 7, 'deflate,gzip'],
            ['br', 11, 'gzip,br'],
            ['br', 11, 'br,gzip'],
            ['br', 11, 'br, gzip'],
            ['gzip', 7, 'gzip;q=1.0, identity; q=0.5, *;q=0'],
            ['gzip', 7, 'compress, gzip'],
            ['gzip', 7, 'compress;q=0.5, gzip;q=1.0'],
            ['br', 11, '*'],
            ['gzip', 7, ';o1ehp912oukhdbkejbdA'],
        ];
    }

    /**
     * @dataProvider provideAcceptEncodingHeadersToTestCompression
     * @param $method
     * @param $level
     * @param $header
     */
    public function testCompressionIfItEnabledInConfig($method, $level, $header)
    {
        // also this checks compression priority
        $conf = new Configuration(__DIR__ . '/../configuration', 'tests');
        $conf->load();

        $c = new Compress($conf);
        $c->prepare();

        $request = $this->createMock(Request::class);
        $request->server['request_uri'] = '/index.html';
        $request->header['accept-encoding'] = $header;

        $response = $this->createMock(Response::class);
        $response
            ->method('header')
            ->with('Content-Encoding', $method)
            ->willReturn(null);

        $body = self::LOREM_IPSUM;
        $cached = self::$cached;
        $c->handle($body, $cached, $request, $response);

        $ex = CompressionFactory::create($method, $level)->compress(self::LOREM_IPSUM);

        $this->assertSame($ex, $body);
        $this->assertSame(self::$cached, $cached);
    }

    public function testCompressionIfItEnabledInConfigAndExtensionNotMatch()
    {
        $conf = new Configuration(__DIR__ . '/../configuration', 'tests');
        $conf->load();

        $c = new Compress($conf);
        $c->prepare();

        $request = $this->createMock(Request::class);
        $request->server['request_uri'] = '/index.html';
        $request->header['accept-encoding'] = '12w12w';

        $response = $this->createMock(Response::class);

        self::$cached['extension']['/index.html'] = 'qwe';

        $body = self::LOREM_IPSUM;
        $cached = self::$cached;
        $c->handle($body, $cached, $request, $response);

        $this->assertSame(self::LOREM_IPSUM, $body);
        $this->assertSame(self::$cached, $cached);
    }
}
