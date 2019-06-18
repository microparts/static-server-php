<?php declare(strict_types=1);

namespace StaticServer\Tests\Compression;

use StaticServer\Compression\GzipCompression;
use StaticServer\Tests\TestCase;

class GzipCompressionTest extends TestCase
{
    public function testGzipCompressionWithCustomLevel()
    {
        $c = new GzipCompression(1);
        $this->assertSame(gzencode(self::LOREM_IPSUM, 1), $c->compress(self::LOREM_IPSUM));

        $c = new GzipCompression(9);
        $this->assertSame(gzencode(self::LOREM_IPSUM, 9), $c->compress(self::LOREM_IPSUM));
    }
}
