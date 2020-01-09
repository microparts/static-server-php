<?php declare(strict_types=1);

namespace StaticServer\Modifier\Iterator;

use InvalidArgumentException;
use Microparts\Configuration\ConfigurationAwareInterface;
use Microparts\Configuration\ConfigurationAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

final class RecursiveIterator implements IteratorInterface, ConfigurationAwareInterface, LoggerAwareInterface
{
    use ConfigurationAwareTrait, LoggerAwareTrait;

    /**
     * Iterate files in server.root.
     *
     * @return iterable<Transfer>
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

            $realpath = $item->getRealPath();

            $this->logger->debug('Iterator. Processing real file: ' . $realpath);

            if (!$realpath) {
                throw new RuntimeException('Unexpected error.');
            }

            $transfer = new Transfer();
            $transfer->filename  = $item->getFilename();
            $transfer->realpath  = $realpath;
            $transfer->extension = $item->getExtension();
            $transfer->location  = substr($realpath, strlen($path));
            $transfer->content   = (string) file_get_contents($realpath);

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
