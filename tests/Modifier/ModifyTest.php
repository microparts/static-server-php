<?php declare(strict_types=1);

namespace StaticServer\Tests\Modifier;

use Microparts\Configuration\Configuration;
use Psr\Log\NullLogger;
use StaticServer\Iterator\RecursiveIterator;
use StaticServer\Modifier\Modify;
use StaticServer\Modifier\ModifyInterface;
use StaticServer\Modifier\NullModify;
use StaticServer\Tests\TestCase;

class ModifyTest extends TestCase
{
    public function testAddFilesToModify()
    {
        $m = new Modify();
        $m->addModifier(new NullModify());
        $m->addTemplate(__FILE__, '/foobar');

        $conf = new Configuration(__DIR__ . '/../configuration', 'nested');
        $conf->load();

        $it = new RecursiveIterator($conf, new NullLogger());
        /** @var \StaticServer\Transfer[] $array */
        $array = iterator_to_array($m->modify($it->iterate()));

        $this->assertInstanceOf(ModifyInterface::class, $m->getModifiers()[0]);
        $this->assertEquals('ModifyTest.php', $array[0]->getFilename());
        $this->assertEquals(file_get_contents(__FILE__), $array[0]->getContent());
        $this->assertEquals('bla-bla.txt', $array[1]->getFilename());
        $this->assertEquals('file.txt', $array[2]->getFilename());
    }
}
