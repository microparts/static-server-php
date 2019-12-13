<?php declare(strict_types=1);

namespace StaticServer\Tests\Modifier;

use Microparts\Configuration\Configuration;
use Psr\Log\NullLogger;
use StaticServer\Modifier\Iterator\RecursiveIterator;
use StaticServer\Modifier\Modify;
use StaticServer\Modifier\ModifyInterface;
use StaticServer\Modifier\NullModify;
use StaticServer\Tests\TestCase;

class ModifyTest extends TestCase
{
    public function testAddFilesToModify()
    {
        $conf = new Configuration(__DIR__ . '/../configuration', 'nested');
        $conf->load();

        $m = new Modify();
        $m->setLogger(new NullLogger());
        $m->setConfiguration($conf);
        $m->addModifier(new NullModify());
        $m->addTemplate(__FILE__, '/foobar');

        $it = new RecursiveIterator();
        $it->setLogger(new NullLogger());
        $it->setConfiguration($conf);
        /** @var \StaticServer\Modifier\Iterator\Transfer[] $array */
        $m->modifyAndSaveToDisk($it->iterate());

        $path = realpath($conf->get('server.modify.root'));
        $this->assertFileExists($path . '/nested/bla-bla.txt');
        $this->assertFileExists($path . '/foobar');
        $this->assertEquals(file_get_contents(__FILE__), file_get_contents($path . '/foobar'));
    }
}
