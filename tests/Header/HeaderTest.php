<?php declare(strict_types=1);

namespace StaticServer\Tests\Header;

use InvalidArgumentException;
use Microparts\Configuration\Configuration;
use StaticServer\Header\ConvertsHeader;
use StaticServer\Tests\TestCase;

class HeaderTest extends TestCase
{
    public function testHeaderConverter()
    {
        $conf = new Configuration(__DIR__ . '/../configuration', 'nested');
        $conf->load();

        $ch = new ConvertsHeader();
        $headers = $ch->convert($conf);

        $featurePolicy = "geolocation 'none'; payment 'none'; microphone 'none'; camera 'none'; autoplay 'none'";
        $link = '<https://example.com/font.woff2>; rel=preload; as=font; type="font/woff2", <https://example.com/app/script.js>; rel=preload; as=script';

        $this->assertSame($featurePolicy, $headers['Feature-Policy']);
        $this->assertSame($link, $headers['Link']);
        $this->assertSame('1; mode=block', $headers['X-XSS-Protection']);
    }

    public function testHowInvalidHeaderNameCheckWorks()
    {
        $conf = new Configuration(__DIR__ . '/../configuration', 'invalid_header_name');
        $conf->load();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header not supported.');

        $ch = new ConvertsHeader();
        $ch->convert($conf);
    }

    public function testHowInvalidHeaderValueCheckWorks()
    {
        $conf = new Configuration(__DIR__ . '/../configuration', 'invalid_header_value');
        $conf->load();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid header format, see docs & examples.');

        $h = new ConvertsHeader();
        $h->convert($conf);
    }
}
