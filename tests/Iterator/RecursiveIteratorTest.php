<?php declare(strict_types=1);

namespace StaticServer\Tests\Iterator;

use InvalidArgumentException;
use Microparts\Configuration\Configuration;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use StaticServer\Iterator\RecursiveIterator;
use StaticServer\Transfer;

class RecursiveIteratorTest extends TestCase
{
    public function testHowToIteratorLoadFilesToTransferObject()
    {
        $file = __DIR__ . '/../example_dist/simple/nested/bla-bla.txt';

        $conf = new Configuration(__DIR__ . '/../configuration', 'nested');
        $conf->load();

        $it = new RecursiveIterator($conf, new NullLogger());
        /** @var Transfer[] $array */
        $array = iterator_to_array($it->iterate());

        $this->assertInstanceOf(Transfer::class, $array[0]);
        $this->assertEquals('bla-bla.txt', $array[0]->getFilename());
        $this->assertEquals('txt', $array[0]->getExtension());
        $this->assertEquals(realpath($file), $array[0]->getRealpath());
        $this->assertEquals('/nested/bla-bla.txt', $array[0]->getLocation());
        $this->assertEquals(file_get_contents($file), $array[0]->getContent());
    }

    public function testHowRecursiveIteratorNotFoundTheFiles()
    {
        $this->expectException(InvalidArgumentException::class);

        $conf = new Configuration(__DIR__ . '/../configuration', 'not_found');
        $conf->load();

        $it = new RecursiveIterator($conf, new NullLogger());
        /** @var Transfer[] $array */
        iterator_to_array($it->iterate());
    }
}
