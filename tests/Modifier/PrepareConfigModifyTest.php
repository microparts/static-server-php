<?php declare(strict_types=1);

namespace StaticServer\Tests\Modifier;

use Microparts\Configuration\Configuration;
use StaticServer\Modifier\SecurityTxtModify;
use StaticServer\Tests\TestCase;
use StaticServer\Transfer;

class PrepareConfigModifyTest extends TestCase
{
    public function testHowHandlerReplaceTemplate()
    {
        $conf = new Configuration(__DIR__ . '/../configuration');
        $conf->load();

        $handler  = new SecurityTxtModify();
        $handler->setConfiguration($conf);

        $path     = realpath(__DIR__ . '/../../src/stub/security.txt');
        $transfer = new Transfer('security.txt', $path, 'js', '/.well-known/security.txt');
        $transfer->setContent(file_get_contents($path));

        $changed = $handler(clone $transfer, $transfer);

        $this->assertStringContainsString("Contact: security@teamc.io\nPreferred-Languages: en, ru", $changed->getContent());
    }
}
