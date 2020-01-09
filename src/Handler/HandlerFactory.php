<?php declare(strict_types=1);

namespace StaticServer\Handler;

use InvalidArgumentException;

class HandlerFactory
{
    /**
     * @param string $name
     * @param array<string, string> $options
     * @return \StaticServer\Handler\NginxHandler
     */
    public static function createHandler(string $name, array $options = [])
    {
        switch ($name) {
            case 'nginx':
                return new NginxHandler($options);
        }

        throw new InvalidArgumentException('Handler does not exists or not defined here.');
    }
}
