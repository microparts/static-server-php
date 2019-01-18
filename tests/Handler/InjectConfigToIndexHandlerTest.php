<?php declare(strict_types=1);
/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 * Date: 2019-01-18
 */

namespace StaticServer\Tests\Handler;

use Microparts\Configuration\Configuration;
use Microparts\Configuration\ConfigurationInterface;
use SplFileInfo;
use StaticServer\Handler\InjectConfigToIndexHandler;
use StaticServer\Tests\TestCase;
use StaticServer\Transfer;

class InjectConfigToIndexHandlerTest extends TestCase
{
    public function testHowInjectingSkipFilesExceptIndex()
    {
        $conf = $this->createMock(ConfigurationInterface::class);
        $handler = new InjectConfigToIndexHandler($conf, 'local', 'sha1_of_code');

        $path = realpath(__DIR__ . '/../example_dist/simple/nested/bla-bla.txt');
        $transfer = new Transfer('bla-bla.txt', $path, 'txt');

        $results =  $handler($transfer, new SplFileInfo($path));

        $this->assertSame($transfer, $results);
    }

    public function testHowInjectingWorks()
    {
        $conf = new Configuration(__DIR__ . '/../configuration', 'local');
        $conf->load();

        $handler = new InjectConfigToIndexHandler($conf, 'local', 'sha1_of_code');

        $path = realpath(__DIR__ . '/../example_dist/vue/index.html');
        $transfer = new Transfer('bla-bla.txt', $path, 'txt');
        $transfer->setContent(file_get_contents($path));

        $results =  $handler($transfer, new SplFileInfo($path));

        $this->assertRegExp('/window\.__stage = \'local\'/', $results->getContent());
        $this->assertRegExp('/window\.__config/', $results->getContent());
        $this->assertRegExp('/window\.__vcs = \'sha1_of_code\'/', $results->getContent());
        $this->assertRegExp('/console\.log/', $results->getContent());
        $this->assertNotContains('"server":', $results->getContent());
    }
}
