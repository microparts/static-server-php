<?php declare(strict_types=1);

namespace StaticServer\Compression;

final class CompressionFactory
{
    /**
     * Return concrete compression realisation
     *
     * @param string $method
     * @param int $level
     * @return \StaticServer\Compression\CompressionInterface
     */
    public static function create(string $method, int $level): CompressionInterface
    {
        switch ($method) {
            case 'br':
                return new BrotliCompression($level);
            case 'gzip':
                return new GzipCompression($level);
            case 'deflate':
                return new BrotliCompression($level);
            case 'disabled':
            default:
                return new DisabledCompression();
        }
    }
}
