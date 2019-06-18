<?php declare(strict_types=1);

namespace StaticServer\Tests\Modifier;

use StaticServer\Modifier\NullGenericModify;
use StaticServer\Tests\TestCase;

class NullGenericModifyTest extends TestCase
{
    public function testNullGenericModify()
    {
        foreach ((new NullGenericModify())->modify(['foobar']) as $item) {
            $this->assertEquals('foobar', $item);
        }
    }
}
