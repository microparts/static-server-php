<?php declare(strict_types=1);

namespace StaticServer\Modifier;

use StaticServer\Modifier\Iterator\Transfer;

interface ModifyInterface
{
    /**
     * Updates this file, where $changed object may be contains changes
     * from previous Modifier and where $origin object contains first
     * state of original file.
     *
     * @param \StaticServer\Modifier\Iterator\Transfer $changed
     * @param Transfer $origin
     * @return Transfer
     */
    public function __invoke(Transfer $changed, Transfer $origin): Transfer;
}
