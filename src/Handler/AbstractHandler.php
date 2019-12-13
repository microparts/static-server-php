<?php declare(strict_types=1);

namespace StaticServer\Handler;

use Microparts\Configuration\ConfigurationAwareInterface;
use Microparts\Configuration\ConfigurationAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Process\Process;

abstract class AbstractHandler implements HandlerInterface, ConfigurationAwareInterface, LoggerAwareInterface
{
    use ConfigurationAwareTrait, LoggerAwareTrait;

    /**
     * @return string
     */
    protected function getServerRoot(): string
    {
        return $this->configuration->get('server.modify.enabled')
            ? $this->configuration->get('server.modify.root')
            : $this->configuration->get('server.root');
    }

    /**
     * @param array<string> $args
     * @param callable|null $callback
     */
    protected function runProcess(array $args, ?callable $callback = null): void
    {
        $proc = new Process($args);
        $proc->setTimeout(null);
        $proc->start();

        if ($callback) {
            $callback($proc);
        }

        $proc->wait(function ($type, $buffer) {
            $this->logger->info($buffer);
        });
    }
}
