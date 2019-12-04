<?php declare(strict_types=1);

namespace StaticServer\Tests\Modifier;

use InvalidArgumentException;
use Microparts\Configuration\Configuration;
use Microparts\Configuration\ConfigurationInterface;
use StaticServer\Modifier\InjectConfigFileToIndexModify;
use StaticServer\Tests\TestCase;
use StaticServer\Modifier\Iterator\Transfer;

class InjectConfigToIndexHandlerTest extends TestCase
{
    public function testHowInjectingSkipFilesExceptIndex()
    {
        $conf = $this->createMock(ConfigurationInterface::class);
        $handler = new InjectConfigFileToIndexModify();
        $handler->setConfiguration($conf);

        $path = realpath(__DIR__ . '/../example_dist/simple/nested/bla-bla.txt');
        $transfer = new Transfer('bla-bla.txt', $path, 'txt', '/bla-bla.txt');

        $results = $handler($transfer, $transfer);

        $this->assertSame($transfer, $results);
    }

    public function testHowInjectingWorksWithStandardCase()
    {
        $results = $this->newInjectHandle('tests_inject_head', '/vue/index.html');
        $this->assertStringContainsString('/__config.js', $results->getContent());
        $this->assertStringContainsString('preload', $results->getContent());
    }

    public function testHowInjectingWorksWithoutHeadSection()
    {
        $results = $this->newInjectHandle('tests_inject_head');
        $this->assertStringContainsString('/__config.js', $results->getContent());
    }

    public function testHowInjectingWorksWithInvalidValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->newInjectHandle('inject_invalid');
    }

    public function testHowInjectingWorksWithEmptyHeadSection()
    {
        $results = $this->newInjectHandle('tests_inject_head', '/empty_index/index.html');
        $this->assertStringNotContainsString('__config', $results->getContent());
        $this->assertStringNotContainsString('preload', $results->getContent());
    }

    public function testHowInjectingWorksWithoutScriptTag()
    {
        $results = $this->newInjectHandle('tests', '/empty_index/index.html');
        $this->assertStringNotContainsString('__config', $results->getContent());
        $this->assertStringNotContainsString('preload', $results->getContent());
    }

    public function testHowInjectingPreloading()
    {
        $results = $this->newInjectHandle('tests', '/head_link_exists/index.html');
        $this->assertStringContainsString('preload', $results->getContent());

        $results = $this->newInjectHandle('tests', '/head_link_not_exists/index.html');
        $this->assertStringContainsString('preload', $results->getContent());
    }

    /**
     * @param string $config
     * @param string $location
     * @return \StaticServer\Modifier\Iterator\Transfer
     */
    private function newInjectHandle(string $config, string $location = '/empty_head/index.html'): Transfer
    {
        $conf = new Configuration(__DIR__ . '/../configuration', $config);
        $conf->load();

        $handler = new InjectConfigFileToIndexModify();
        $handler->setConfiguration($conf);

        $path = realpath(__DIR__ . '/../example_dist' . $location);
        $transfer = new Transfer('index.html', $path, 'html', '/index.html');
        $transfer->setContent(file_get_contents($path));

        return $handler($transfer, $transfer);
    }
}
