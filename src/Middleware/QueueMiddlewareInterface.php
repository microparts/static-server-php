<?php declare(strict_types=1);
/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 * Date: 2019-01-16
 */

namespace StaticServer\Middleware;

use SplQueue;
use Swoole\Http\Request;
use Swoole\Http\Response;

interface QueueMiddlewareInterface
{
    /**
     * @return string
     */
    public function listen(): string;

    /**
     * @param \SplQueue $queue
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     * @return mixed
     */
    public function process(SplQueue $queue, Request $request, Response $response): void;
}
