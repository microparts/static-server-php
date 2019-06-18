<?php declare(strict_types=1);

namespace StaticServer\Tests\Compression;

use StaticServer\Compression\BrotliCompression;
use StaticServer\Compression\CompressionFactory;
use StaticServer\Compression\DeflateCompression;
use StaticServer\Compression\GzipCompression;
use StaticServer\Tests\TestCase;

class CompressionFactoryTest extends TestCase
{
    public function testHowCompressionFactoryCreateObjectsFromString()
    {
        $this->assertInstanceOf(BrotliCompression::class, CompressionFactory::create('br', 1));
        $this->assertInstanceOf(GzipCompression::class, CompressionFactory::create('gzip', 1));
        $this->assertInstanceOf(DeflateCompression::class, CompressionFactory::create('deflate', 1));
        $this->assertInstanceOf(GzipCompression::class, CompressionFactory::create('err', 1));
    }
}
