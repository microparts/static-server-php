<?php declare(strict_types=1);

namespace StaticServer\Modifier;

use StaticServer\Modifier\Iterator\Transfer;

class NullModify implements ModifyInterface
{
    /**
     * Updates this file, where $changed object may be contains changes
     * from previous Modifier and where $origin object contains first
     * state of original file.
     *
     * @param \StaticServer\Modifier\Iterator\Transfer $changed
     * @param \StaticServer\Modifier\Iterator\Transfer $origin
     * @return \StaticServer\Modifier\Iterator\Transfer
     */
    public function __invoke(Transfer $changed, Transfer $origin): Transfer
    {
        return $changed;
    }
}
