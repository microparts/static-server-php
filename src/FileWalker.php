<?php declare(strict_types=1);
/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 * Date: 2019-01-16
 */

namespace StaticServer;

use Generator;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplQueue;
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
    private $handler;

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
        $this->handler[] = $handler;
    }

    /**
     * Recursive file walker.
     *
     * @param string $path
     * @return iterable
     */
    public function walk(string $path = __DIR__ . '/dist'): iterable
    {
//        $this->addHandler(new SaveChangesHandler());

        $directory = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($directory);

        $format = 'Files founded in %s, count: %d';
        $this->logger->debug(sprintf($format, $path, iterator_count($iterator)));

        /** @var RecursiveDirectoryIterator $item */
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                continue;
            }

            $this->logger->debug('Processing file: ' . $item->getRealPath());

            yield array_reduce($this->handler, function ($carry, HandlerInterface $handler) use ($item) {
                return $handler($carry, $item);
            });
        }
    }
}
