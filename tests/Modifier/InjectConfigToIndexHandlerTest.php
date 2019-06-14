<?php declare(strict_types=1);

namespace StaticServer\Tests\Modifier;

use Microparts\Configuration\Configuration;
use Microparts\Configuration\ConfigurationInterface;
use StaticServer\Modifier\InjectConfigFileToIndexModify;
use StaticServer\Tests\TestCase;
use StaticServer\Transfer;

class InjectConfigToIndexHandlerTest extends TestCase
{
    public function testHowInjectingSkipFilesExceptIndex()
    {
        $conf = $this->createMock(ConfigurationInterface::class);
        $handler = new InjectConfigFileToIndexModify($conf);

        $path = realpath(__DIR__ . '/../example_dist/simple/nested/bla-bla.txt');
        $transfer = new Transfer('bla-bla.txt', $path, 'txt', '/bla-bla.txt');

        $results = $handler($transfer, $transfer);

        $this->assertSame($transfer, $results);
    }

    public function testHowInjectingWorksWithStandardCase()
    {
        $conf = new Configuration(__DIR__ . '/../configuration', 'tests_inject_head');
        $conf->load();

        $handler = new InjectConfigFileToIndexModify($conf);

        $path = realpath(__DIR__ . '/../example_dist/vue/index.html');
        $transfer = new Transfer('index.html', $path, 'html', '/index.html');
        $transfer->setContent(file_get_contents($path));

        $results =  $handler($transfer, $transfer);

        $this->assertInject($results);
    }

    public function testHowInjectingWorksWithoutHeadSection()
    {
        $conf = new Configuration(__DIR__ . '/../configuration', 'tests_inject_head');
        $conf->load();

        $handler = new InjectConfigFileToIndexModify($conf);

        $path = realpath(__DIR__ . '/../example_dist/empty_head/index.html');
        $transfer = new Transfer('index.html', $path, 'html', '/index.html');
        $transfer->setContent(file_get_contents($path));

        $results = $handler($transfer, $transfer);

        $this->assertInject($results);
    }

    private function assertInject(Transfer $transfer)
    {
        $this->assertStringContainsString('/__config.js', $transfer->getContent());
    }
}
