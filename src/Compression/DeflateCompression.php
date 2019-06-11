<?php declare(strict_types=1);

namespace StaticServer\Compression;


final class DeflateCompression implements CompressionInterface
{
    /**
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
     * @return mixed
     */
    public function compress(string $data): string
    {
        return gzdeflate($data, $this->level);
    }
}
