<?php declare(strict_types=1);

namespace StaticServer\Generic;

interface ClearCacheInterface
{
    /**
     * Clear an object cache.
     *
     * @return void
     */
    public function clearCache(): void;
}
