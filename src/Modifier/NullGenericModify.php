<?php declare(strict_types=1);

namespace StaticServer\Modifier;

/**
 * Class NullGenericModify
 *
 * @codeCoverageIgnore
 * @package StaticServer\Modifier
 */
final class NullGenericModify implements GenericModifyInterface
{
    /**
     * Nothing to modify if modifier not set up.
     *
     * @param iterable<\StaticServer\Modifier\Iterator\Transfer> $files
     *
     * @return void
     */
    public function modifyAndSaveToDisk(iterable $files): void
    {
        // black hole
    }
}
