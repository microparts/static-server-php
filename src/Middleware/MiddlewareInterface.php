<?php declare(strict_types=1);

namespace StaticServer\Middleware;

use Swoole\Http\Request;
use Swoole\Http\Response;

interface MiddlewareInterface
{
    /**
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     * @return mixed
     */
    public function process(Request $request, Response $response): void;
}
