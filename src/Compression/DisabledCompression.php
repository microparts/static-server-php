<?php declare(strict_types=1);

namespace StaticServer\Compression;


final class DisabledCompression implements CompressionInterface
{
    /**
     * Compress data with any options.
     *
     * @param string $data
     * @return mixed
     */
    public function compress(string $data): string
    {
        return $data;
    }
}
