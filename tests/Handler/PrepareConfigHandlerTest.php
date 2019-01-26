<?php declare(strict_types=1);

/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 */

namespace StaticServer\Tests\Handler;

use Microparts\Configuration\Configuration;
use SplFileInfo;
use StaticServer\Handler\LoadContentHandler;
use StaticServer\Handler\PrepareConfigHandler;
use StaticServer\Tests\TestCase;

class PrepareConfigHandlerTest extends TestCase
{
    public function testHowHandlerReplaceTemplate()
    {
        $conf = new Configuration(__DIR__ . '/../configuration');
        $conf->load();

        $load = new LoadContentHandler();
        $handler = new PrepareConfigHandler($conf, 'local', 'sha1_of_code');
        $file = new SplFileInfo(__DIR__ . '/../../src/stub/__config.js');
        $transfer = $handler($load(null, $file), $file);

        $this->assertRegExp('/window\.__stage = \'local\'/', $transfer->getContent());
        $this->assertRegExp('/window\.__config/', $transfer->getContent());
        $this->assertRegExp('/window\.__vcs = \'sha1_of_code\'/', $transfer->getContent());
        $this->assertRegExp('/console\.log/', $transfer->getContent());
        $this->assertNotContains('"server":', $transfer->getContent());
    }
}
