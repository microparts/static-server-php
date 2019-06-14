<?php declare(strict_types=1);

namespace StaticServer\Compression;

interface CompressionInterface
{
    /**
     * Compress data.
     *
     * @param string $data
     *
     * @return string
     */
    public function compress(string $data): string;
}
