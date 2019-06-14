<?php declare(strict_types=1);

namespace StaticServer\Compression;

final class GzipCompression implements CompressionInterface
{
    /**
     * Compression level.
     * From 1 to 9.
     *
     * @var int
     */
    private $level;

    /**
     * GzipCompression constructor.
     *
     * @param int $level
     */
    public function __construct(int $level)
    {
        $this->level = $level;
    }

    /**
     * Compress data.
     *
     * @param string $data
     *
     * @return string
     */
    public function compress(string $data): string
    {
        return gzencode($data, $this->level);
    }
}
