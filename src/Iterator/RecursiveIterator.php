<?php declare(strict_types=1);

namespace StaticServer\Iterator;

use InvalidArgumentException;
use Microparts\Configuration\ConfigurationInterface;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use StaticServer\Transfer;

final class RecursiveIterator implements IteratorInterface
{
    /**
     * @var \Microparts\Configuration\ConfigurationInterface
     */
    private $conf;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * RecursiveIterator constructor.
     *
     * @param \Microparts\Configuration\ConfigurationInterface $conf
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(ConfigurationInterface $conf, LoggerInterface $logger)
    {
        $this->conf   = $conf;
        $this->logger = $logger;
    }

    /**
     * Iterate files in server.root.
     *
     * @return iterable
     */
    public function iterate(): iterable
    {
        $path = $this->getRootPath();

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

            yield new Transfer(
                $item->getFilename(),
                $item->getRealPath(),
                $item->getExtension(),
                substr($item->getRealPath(), strlen($path)),
                file_get_contents($item->getRealPath())
            );
        }
    }

    /**
     * Check if server.root is exists and get realpath.
     *
     * @return string
     */
    private function getRootPath(): string
    {
        $root = realpath($this->conf->get('server.root'));

        // If it exist, check if it's a directory
        if($root !== false && is_dir($root)) {
            return $root;
        }

        throw new InvalidArgumentException('Root server directory not found or it is not directory.');
    }
}
