<?php declare(strict_types=1);

namespace StaticServer\Modifier;

final class NullGenericModify implements GenericModifyInterface
{
    /**
     * Nothing to modify if modifier not set up.
     *
     * @param iterable $files
     *
     * @return void
     */
    public function modifyAndSaveToDisk(iterable $files): void
    {
        // black hole
    }
}
