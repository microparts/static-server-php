<?php
/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 * Date: 2019-06-04
 */

namespace StaticServer\Compression;


interface CompressionInterface
{
    /**
     * Compress data.
     *
     * @param string $data
     * @return mixed
     */
    public function compress(string $data): string;
}
