<?php declare(strict_types=1);

namespace StaticServer\Handler;

use SplFileInfo;
use StaticServer\Transfer;

final class LoadContentHandler implements HandlerInterface
{
    /**
     * @param $carry
     * @param \SplFileInfo $item
     * @return Transfer
     */
    public function __invoke($carry, SplFileInfo $item): Transfer
    {
        return new Transfer(
            $item->getFilename(),
            $item->getRealPath(),
            $item->getExtension(),
            file_get_contents($item->getRealPath())
        );
    }
}
