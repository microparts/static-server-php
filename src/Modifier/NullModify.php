<?php declare(strict_types=1);

namespace StaticServer\Modifier;

final class NullModify implements GenericModifyInterface
{
    /**
     * @param iterable $files
     * @return iterable
     */
    public function modify(iterable $files): iterable
    {
        yield from $files;
    }
}
