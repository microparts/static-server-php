<?php declare(strict_types=1);

namespace StaticServer\Tests\Compression;

use StaticServer\Compression\BrotliCompression;
use StaticServer\Tests\TestCase;

class BrotliCompressionTest extends TestCase
{
    public function testBrotliCompressionWithCustomLevel()
    {
        $c = new BrotliCompression(1);
        $this->assertSame(brotli_compress(self::LOREM_IPSUM, 1), $c->compress(self::LOREM_IPSUM));

        $c = new BrotliCompression(11);
        $this->assertSame(brotli_compress(self::LOREM_IPSUM, 11), $c->compress(self::LOREM_IPSUM));
    }
}


