<?php declare(strict_types=1);

namespace StaticServer\Tests\Iterator;

use InvalidArgumentException;
use Microparts\Configuration\Configuration;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use StaticServer\Modifier\Iterator\RecursiveIterator;
use StaticServer\Modifier\Iterator\Transfer;

class RecursiveIteratorTest extends TestCase
{
    public function testHowToIteratorLoadFilesToTransferObject()
    {
        $file = __DIR__ . '/../example_dist/simple/nested/bla-bla.txt';

        $conf = new Configuration(__DIR__ . '/../configuration', 'nested');
        $conf->load();

        $it = new RecursiveIterator();
        $it->setLogger(new NullLogger());
        $it->setConfiguration($conf);

        $transfer = null;
        foreach ($it->iterate() as $item) {
            /** @var \StaticServer\Modifier\Iterator\Transfer $item */
            $this->assertInstanceOf(Transfer::class, $item);
            if ($item->filename === 'bla-bla.txt') {
                $transfer = $item;
                break;
            }
        }

        $this->assertEquals('txt', $transfer->extension);
        $this->assertEquals('/modified/nested/bla-bla.txt', $transfer->location);
        $this->assertEquals(file_get_contents($file), $transfer->content);
    }

    public function testHowRecursiveIteratorNotFoundTheFiles()
    {
        $this->expectException(InvalidArgumentException::class);

        $conf = new Configuration(__DIR__ . '/../configuration', 'not_found');
        $conf->load();

        $it = new RecursiveIterator();
        $it->setLogger(new NullLogger());
        $it->setConfiguration($conf);

        /** @var \StaticServer\Modifier\Iterator\Transfer[] $array */
        iterator_to_array($it->iterate());
    }
}
