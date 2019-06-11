<?php declare(strict_types=1);

namespace StaticServer\Modifier;

use StaticServer\Transfer;

interface ModifyInterface
{
    /**
     * @param \StaticServer\Transfer $changed
     * @param \StaticServer\Transfer $origin
     * @return \StaticServer\Transfer
     */
    public function __invoke(Transfer $changed, Transfer $origin): Transfer;
}
