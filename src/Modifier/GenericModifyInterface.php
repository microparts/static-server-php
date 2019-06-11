<?php declare(strict_types=1);

namespace StaticServer\Modifier;

interface GenericModifyInterface
{
    public function modify(iterable $files): iterable;
}
