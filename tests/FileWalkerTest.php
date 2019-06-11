<?php declare(strict_types=1);

namespace StaticServer\Tests;

use Microparts\Configuration\ConfigurationInterface;
use Psr\Log\NullLogger;
use StaticServer\Modifier\Modify;
use StaticServer\Modifier\InjectConfigFileToIndexModify;
use StaticServer\Modifier\LoadContentModify;

class FileWalkerTest extends TestCase
{
    public function testDefaultHandlerIsInitialized()
    {
        $walker = new Modify(new NullLogger());

        $this->assertInstanceOf(LoadContentModify::class, $walker->getModifiers()[0]);
    }

    public function testHandlerIsAlone()
    {
        $walker = new Modify(new NullLogger());

        $this->assertCount(1, $walker->getModifiers());
    }

    public function testHandlerIsCanBeAdded()
    {
        $walker = new Modify(new NullLogger());

        $conf = $this->createMock(ConfigurationInterface::class);
        $walker->addModifier(new InjectConfigFileToIndexModify($conf));

        $this->assertCount(2, $walker->getModifiers());
        $this->assertInstanceOf(InjectConfigFileToIndexModify::class, $walker->getModifiers()[1]);
    }

    public function testHowWalkerWalkIntoNestedDirs()
    {
        $walker = new Modify(new NullLogger());
        $gen = $walker->walk(__DIR__ . '/example_dist/simple');

        $counter = 0;
        /** @var \StaticServer\Transfer $item */
        foreach ($gen as $item) {
            if ($item->getFilename() === 'bla-bla.txt') {
                $this->assertSame('bla-bla' . PHP_EOL, $item->getContent());
                $this->assertSame('txt', $item->getExtension());
                $this->assertSame(__DIR__ . '/example_dist/simple/nested/bla-bla.txt', $item->getRealpath());
                ++$counter;
            }

            if ($item->getFilename() === 'file.txt') {
                $this->assertSame('file' . PHP_EOL, $item->getContent());
                $this->assertSame('txt', $item->getExtension());
                $this->assertSame(__DIR__ . '/example_dist/simple/nested/foo/bar/file.txt', $item->getRealpath());
                ++$counter;
            }
        }

        $this->assertSame(2, $counter);
    }

    public function testHowWalkerWalkInTheEmptyDir()
    {
        $walker = new Modify(new NullLogger());
        $gen = $walker->walk(__DIR__ . '/example_dist/empty');

        $this->assertSame([], iterator_to_array($gen));
    }

    public function testHowWalkerSkipFolders()
    {
        $walker = new Modify(new NullLogger());
        $gen = $walker->walk(__DIR__ . '/example_dist/vue');

        /** @var \StaticServer\Transfer $item */
        foreach ($gen as $item) {
            $this->assertFalse(is_dir($item->getRealpath()));
        }
    }
}
