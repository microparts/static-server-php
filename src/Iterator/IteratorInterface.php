<?php declare(strict_types=1);

namespace StaticServer\Iterator;

interface IteratorInterface
{
    public function iterate(): iterable;
}
