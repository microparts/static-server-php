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

        $transfer = null;
        foreach ($it->iterate() as $item) {
            /** @var Transfer $item */
            $this->assertInstanceOf(Transfer::class, $item);
            if ($item->getFilename() === 'bla-bla.txt') {
                $transfer = $item;
                break;
            }
        }

        $this->assertEquals('txt', $transfer->getExtension());
        $this->assertEquals(realpath($file), $transfer->getRealpath());
        $this->assertEquals('/nested/bla-bla.txt', $transfer->getLocation());
        $this->assertEquals(file_get_contents($file), $transfer->getContent());
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
