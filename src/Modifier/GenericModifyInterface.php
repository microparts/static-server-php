<?php declare(strict_types=1);

namespace StaticServer\Modifier;

interface GenericModifyInterface
{
    /**
     * Method for modify incoming files
     * or add new one.
     *
     * @param iterable $files
     *
     * @return iterable
     */
    public function modify(iterable $files): iterable;
}
