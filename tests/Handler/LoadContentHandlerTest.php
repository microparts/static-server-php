<?php declare(strict_types=1);
/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 * Date: 2019-01-18
 */

namespace StaticServer\Tests\Handler;

use SplFileInfo;
use StaticServer\Handler\LoadContentHandler;
use StaticServer\Tests\TestCase;

class LoadContentHandlerTest extends TestCase
{
    public function testLoadContentForConcreteFile()
    {
        $handler = new LoadContentHandler();

        $path = realpath(__DIR__ . '/../example_dist/simple/nested/bla-bla.txt');

        /** @var \StaticServer\Transfer $transfer */
        $transfer = $handler(null, new SplFileInfo($path));

        $this->assertSame($path, $transfer->getRealpath());
        $this->assertSame('bla-bla.txt', $transfer->getFilename());
        $this->assertSame('txt', $transfer->getExtension());
        $this->assertSame('bla-bla' . PHP_EOL, $transfer->getContent());
    }
}
