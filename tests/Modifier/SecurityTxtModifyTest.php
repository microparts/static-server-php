<?php declare(strict_types=1);

namespace StaticServer\Tests\Modifier;

use Microparts\Configuration\Configuration;
use StaticServer\Modifier\PrepareConfigModify;
use StaticServer\Tests\TestCase;
use StaticServer\Modifier\Iterator\Transfer;

class SecurityTxtModifyTest extends TestCase
{
    public function testHowSecurityTxtHandlerReplaceTemplate()
    {
        $conf = new Configuration(__DIR__ . '/../configuration');
        $conf->load();

        $handler  = new PrepareConfigModify('local', 'sha1_of_code');
        $handler->setConfiguration($conf);

        $path     = realpath(__DIR__ . '/../../src/stub/__config.js');
        $transfer = new Transfer();
        $transfer->filename = '__config.js';
        $transfer->realpath = $path;
        $transfer->extension = 'js';
        $transfer->location = '/__config.js';
        $transfer->content = file_get_contents($path);

        $changed = $handler(clone $transfer, $transfer);

        $this->assertRegExp('/window\.__stage=\'local\'/', $changed->content);
        $this->assertRegExp('/window\.__config=JSON\.parse/', $changed->content);
        $this->assertRegExp('/window\.__vcs=\'sha1_of_code\'/', $changed->content);
        $this->assertRegExp('/console\.log/', $changed->content);
        $this->assertStringNotContainsString('"server":', $changed->content);
    }
}
