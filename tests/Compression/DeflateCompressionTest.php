<?php declare(strict_types=1);

namespace StaticServer\Tests\Compression;

use StaticServer\Compression\DeflateCompression;
use StaticServer\Tests\TestCase;

class DeflateCompressionTest extends TestCase
{
    public function testDeflateCompressionWithCustomLevel()
    {
        $c = new DeflateCompression(1);
        $this->assertSame(gzdeflate(self::LOREM_IPSUM, 1), $c->compress(self::LOREM_IPSUM));

        $c = new DeflateCompression(7);
        $this->assertSame(gzdeflate(self::LOREM_IPSUM, 7), $c->compress(self::LOREM_IPSUM));
    }
}
