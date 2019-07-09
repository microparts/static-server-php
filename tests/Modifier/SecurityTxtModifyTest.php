<?php declare(strict_types=1);

namespace StaticServer\Tests\Modifier;

use Microparts\Configuration\Configuration;
use StaticServer\Modifier\PrepareConfigModify;
use StaticServer\Tests\TestCase;
use StaticServer\Transfer;

class SecurityTxtModifyTest extends TestCase
{
    public function testHowSecurityTxtHandlerReplaceTemplate()
    {
        $conf = new Configuration(__DIR__ . '/../configuration');
        $conf->load();

        $handler  = new PrepareConfigModify($conf, 'local', 'sha1_of_code');
        $path     = realpath(__DIR__ . '/../../src/stub/__config.js');
        $transfer = new Transfer('__config.js', $path, 'js', '/__config.js');
        $transfer->setContent(file_get_contents($path));

        $changed = $handler(clone $transfer, $transfer);

        $this->assertRegExp('/window\.__stage=\'local\'/', $changed->getContent());
        $this->assertRegExp('/window\.__config=JSON\.parse/', $changed->getContent());
        $this->assertRegExp('/window\.__vcs=\'sha1_of_code\'/', $changed->getContent());
        $this->assertRegExp('/console\.log/', $changed->getContent());
        $this->assertStringNotContainsString('"server":', $changed->getContent());
    }
}
