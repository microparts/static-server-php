<?php declare(strict_types=1);

namespace StaticServer\Handler;

use Microparts\Configuration\ConfigurationAwareInterface;
use Microparts\Configuration\ConfigurationAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

abstract class AbstractHandler implements HandlerInterface, ConfigurationAwareInterface, LoggerAwareInterface
{
    use ConfigurationAwareTrait, LoggerAwareTrait;

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected Filesystem $filesystem;

    /**
     * AbstractHandler constructor.
     */
    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * @return string
     */
    protected function getServerRoot(): string
    {
        $path = $this->configuration->get('server.modify.enabled')
            ? $this->configuration->get('server.modify.root')
            : $this->configuration->get('server.root');

        return $this->expandPathLikeShell($path);
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

    /**
     * @param string $filename
     */
    protected function touchFile(string $filename): void
    {
        $filename = $this->expandPathLikeShell($filename);
        $this->logger->debug("Touch file: $filename");

        $this->filesystem->touch($filename);
    }

    /**
     * @param array $paths
     */
    protected function makePathForFiles(array $paths): void
    {
        static $created = [];

        foreach ($paths as $path) {
            $path = pathinfo($this->expandPathLikeShell($path), PATHINFO_DIRNAME);

            if (!isset($created[$path])) {
                $this->logger->debug("Create nested dirs for: $path");
                $this->filesystem->mkdir($path, 0755);
                $created[$path]= true;
            }
        }
    }

    /**
     * https://pubs.opengroup.org/onlinepubs/9699919799/utilities/V3_chap02.html#tag_18_06_01
     *
     * @param string $path
     * @return string
     */
    protected function expandPathLikeShell(string $path): string
    {
        if (strpos($path, '~') !== false) {
            $info = posix_getpwuid(posix_getuid());
            $path = str_replace('~', $info['dir'], $path);
        }

        return $path;
    }
}
