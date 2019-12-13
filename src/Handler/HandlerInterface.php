<?php declare(strict_types=1);

namespace StaticServer\Handler;

use StaticServer\Header\HeaderInterface;

interface HandlerInterface
{
    public function checkDependenciesBeforeStart(): void;
    public function generateConfig(HeaderInterface $header): void;
    public function checkConfig(): void;
    public function start(): void;
    public function reload(): void;
    public function stop(): void;
}
