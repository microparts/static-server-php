<?php declare(strict_types=1);

namespace StaticServer\Tests\Modifier;

use Microparts\Configuration\Configuration;
use StaticServer\Modifier\SecurityTxtModify;
use StaticServer\Tests\TestCase;
use StaticServer\Modifier\Iterator\Transfer;

class PrepareConfigModifyTest extends TestCase
{
    public function testHowHandlerReplaceTemplate()
    {
        $conf = new Configuration(__DIR__ . '/../configuration');
        $conf->load();

        $handler  = new SecurityTxtModify();
        $handler->setConfiguration($conf);

        $path     = realpath(__DIR__ . '/../../src/stub/security.txt');
        $transfer = new Transfer();
        $transfer->filename = 'security.txt';
        $transfer->realpath = $path;
        $transfer->extension = 'js';
        $transfer->location = '/.well-known/security.txt';
        $transfer->content = file_get_contents($path);

        $changed = $handler(clone $transfer, $transfer);

        $this->assertStringContainsString("Contact: security@spacetab.io\nPreferred-Languages: en, ru", $changed->content);
    }
}
