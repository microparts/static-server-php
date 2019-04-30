<?php declare(strict_types=1);

namespace StaticServer\Tests;

use Microparts\Configuration\ConfigurationInterface;
use Psr\Log\NullLogger;
use StaticServer\FileWalker;
use StaticServer\Handler\InjectConfigFileToIndexHandler;
use StaticServer\Handler\LoadContentHandler;

class FileWalkerTest extends TestCase
{
    public function testDefaultHandlerIsInitialized()
    {
        $walker = new FileWalker(new NullLogger());

        $this->assertInstanceOf(LoadContentHandler::class, $walker->getHandlers()[0]);
    }

    public function testHandlerIsAlone()
    {
        $walker = new FileWalker(new NullLogger());

        $this->assertCount(1, $walker->getHandlers());
    }

    public function testHandlerIsCanBeAdded()
    {
        $walker = new FileWalker(new NullLogger());

        $conf = $this->createMock(ConfigurationInterface::class);
        $walker->addHandler(new InjectConfigFileToIndexHandler($conf));

        $this->assertCount(2, $walker->getHandlers());
        $this->assertInstanceOf(InjectConfigFileToIndexHandler::class, $walker->getHandlers()[1]);
    }

    public function testHowWalkerWalkIntoNestedDirs()
    {
        $walker = new FileWalker(new NullLogger());
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
        $walker = new FileWalker(new NullLogger());
        $gen = $walker->walk(__DIR__ . '/example_dist/empty');

        $this->assertSame([], iterator_to_array($gen));
    }

    public function testHowWalkerSkipFolders()
    {
        $walker = new FileWalker(new NullLogger());
        $gen = $walker->walk(__DIR__ . '/example_dist/vue');

        /** @var \StaticServer\Transfer $item */
        foreach ($gen as $item) {
            $this->assertFalse(is_dir($item->getRealpath()));
        }
    }
}
