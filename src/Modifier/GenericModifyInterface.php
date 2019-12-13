<?php declare(strict_types=1);

namespace StaticServer\Modifier;

interface GenericModifyInterface
{
    /**
     * Method for modify incoming files
     * or add new one. Then, saves to disk without override original files.
     *
     * @param iterable<\StaticServer\Modifier\Iterator\Transfer> $files
     *
     * @return void
     */
    public function modifyAndSaveToDisk(iterable $files): void;
}
