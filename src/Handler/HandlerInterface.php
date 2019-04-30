<?php declare(strict_types=1);

namespace StaticServer\Handler;

use SplFileInfo;
use StaticServer\Transfer;

interface HandlerInterface
{
    /**
     * @param $carry
     * @param \SplFileInfo $item
     * @return Transfer
     */
    public function __invoke($carry, SplFileInfo $item): Transfer;
}
