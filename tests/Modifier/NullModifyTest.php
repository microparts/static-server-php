<?php declare(strict_types=1);

namespace StaticServer\Tests\Modifier;

use StaticServer\Modifier\NullGenericModify;
use StaticServer\Modifier\NullModify;
use StaticServer\Tests\TestCase;
use StaticServer\Modifier\Iterator\Transfer;

class NullModifyTest extends TestCase
{
    public function testNullModify()
    {
        $m = new NullModify();
        $t = new Transfer('', '', '', '', '');
        $this->assertInstanceOf(Transfer::class, $m(clone $t, $t));
    }
}
