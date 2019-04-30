<?php declare(strict_types=1);

namespace StaticServer;

use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use StaticServer\Handler\HandlerInterface;
use StaticServer\Handler\LoadContentHandler;

final class FileWalker
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $handlers = [];

    /**
     * @var SplFileInfo[]
     */
    private $ghosts = [];

    /**
     * FileWalker constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->addHandler(new LoadContentHandler());
    }

    /**
     * @param $handler
     */
    public function addHandler(HandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }

    /**
     * @param string $path
     */
    public function addGhostFile(string $path): void
    {
        $this->ghosts[] = new SplFileInfo($path);
    }

    /**
     * @return array
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * Recursive file walker.
     *
     * @param string $path
     * @return iterable
     */
    public function walk(string $path = __DIR__ . '/dist'): iterable
    {
        $directory = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($directory);

        $format = 'Files founded in %s, count: %d';
        $this->logger->debug(sprintf($format, $path, iterator_count($iterator)));

        /** @var RecursiveDirectoryIterator $item */
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                continue;
            }

            if (substr($item->getFilename(), 0, 1) === '.') {
                continue;
            }

            $this->logger->debug('Processing file: ' . $item->getRealPath());

            yield array_reduce($this->handlers, function ($carry, HandlerInterface $handler) use ($item) {
                return $handler($carry, $item);
            });
        }

        foreach ($this->ghosts as $ghost) {
            yield array_reduce($this->handlers, function ($carry, HandlerInterface $handler) use ($ghost) {
                return $handler($carry, $ghost);
            });
        }
    }
}
