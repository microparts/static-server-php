<?php declare(strict_types=1);

namespace StaticServer\Tests\Handler;

use SplFileInfo;
use StaticServer\Modifier\LoadContentModify;
use StaticServer\Tests\TestCase;

class LoadContentHandlerTest extends TestCase
{
    public function testLoadContentForConcreteFile()
    {
        $handler = new LoadContentModify();

        $path = realpath(__DIR__ . '/../example_dist/simple/nested/bla-bla.txt');

        /** @var \StaticServer\Transfer $transfer */
        $transfer = $handler(null, new SplFileInfo($path));

        $this->assertSame($path, $transfer->getRealpath());
        $this->assertSame('bla-bla.txt', $transfer->getFilename());
        $this->assertSame('txt', $transfer->getExtension());
        $this->assertSame('bla-bla' . PHP_EOL, $transfer->getContent());
    }
}
