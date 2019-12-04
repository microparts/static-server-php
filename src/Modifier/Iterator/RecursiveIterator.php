<?php declare(strict_types=1);

namespace StaticServer\Modifier\Iterator;

use InvalidArgumentException;
use Microparts\Configuration\ConfigurationAwareInterface;
use Microparts\Configuration\ConfigurationAwareTrait;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class RecursiveIterator implements IteratorInterface, ConfigurationAwareInterface
{
    use ConfigurationAwareTrait;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * RecursiveIterator constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Iterate files in server.root.
     *
     * @return iterable|\Traversable
     */
    public function iterate(): iterable
    {
        $path = $this->getRootPath();

        $directory = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($directory);

        /** @var RecursiveDirectoryIterator $item */
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                continue;
            }

            if (substr($item->getFilename(), 0, 1) === '.') {
                continue;
            }

            $this->logger->debug('Processing file: ' . $item->getRealPath());

            $transfer = new Transfer();
            $transfer->filename  = $item->getFilename();
            $transfer->realpath  = $item->getRealPath();
            $transfer->extension = $item->getExtension();
            $transfer->location  = substr($item->getRealPath(), strlen($path));
            $transfer->content   = file_get_contents($item->getRealPath());

            yield $transfer;
        }
    }

    /**
     * Check if server.root is exists and get realpath.
     *
     * @return string
     */
    private function getRootPath(): string
    {
        $root = realpath($this->configuration->get('server.root'));

        // If it exist, check if it's a directory
        if($root !== false && is_dir($root)) {
            return $root;
        }

        throw new InvalidArgumentException('Root server directory not found or it is not directory.');
    }
}
