<?php declare(strict_types=1);

namespace StaticServer\Modifier;

use StaticServer\Transfer;

interface ModifyInterface
{
    /**
     * Updates this file, where $changed object may be contains changes
     * from previous Modifier and where $origin object contains first
     * state of original file.
     *
     * @param Transfer $changed
     * @param Transfer $origin
     *
     * @return Transfer
     */
    public function __invoke(Transfer $changed, Transfer $origin): Transfer;
}
