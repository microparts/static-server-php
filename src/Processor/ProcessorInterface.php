<?php declare(strict_types=1);

namespace StaticServer\Processor;

use Swoole\Http\Request;
use Swoole\Http\Response;

interface ProcessorInterface
{
    /**
     * Load to memory modified files.
     *
     * @param iterable $files
     *
     * @return void
     */
    public function load(iterable $files): void;

    /**
     * Processes incoming request with defined logic.
     *
     * @param string $body
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     *
     * @return void
     */
    public function process(string & $body, Request $request, Response $response): void;
}
