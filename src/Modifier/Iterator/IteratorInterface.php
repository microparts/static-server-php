<?php declare(strict_types=1);

namespace StaticServer\Modifier\Iterator;

interface IteratorInterface
{
    /**
     * Iterate files in server.root.
     *
     * @return iterable<\StaticServer\Modifier\Iterator\Transfer>
     */
    public function iterate(): iterable;
}
