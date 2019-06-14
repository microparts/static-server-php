<?php declare(strict_types=1);

namespace StaticServer\Compression;


final class BrotliCompression implements CompressionInterface
{
    /**
     * Compression level.
     * From 1 to 11.
     *
     * @var int
     */
    private $level;

    /**
     * BrotliCompression constructor.
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
        return brotli_compress($data, $this->level);
    }
}
