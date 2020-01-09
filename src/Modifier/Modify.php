<?php declare(strict_types=1);

namespace StaticServer\Modifier;

use InvalidArgumentException;
use Microparts\Configuration\ConfigurationAwareInterface;
use Microparts\Configuration\ConfigurationAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use StaticServer\Modifier\Iterator\Transfer;
use Symfony\Component\Filesystem\Filesystem;

final class Modify implements GenericModifyInterface, ConfigurationAwareInterface, LoggerAwareInterface
{
    use ConfigurationAwareTrait, LoggerAwareTrait;

    /**
     * @var array<\StaticServer\Modifier\ModifyInterface>
     */
    private array $modifiers = [];

    /**
     * @var array<string>
     */
    private array $ghosts = [];

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private Filesystem $filesystem;

    /**
     * Modify constructor.
     */
    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * @param \StaticServer\Modifier\ModifyInterface $handler
     *
     * @return void
     */
    public function addModifier(ModifyInterface $handler): void
    {
        $this->modifiers[] = $handler;
    }

    /**
     * $path - path to file
     * $location - serve location http://0.0.0.0:8080/$location
     *
     * @param string $path
     * @param string $location
     *
     * @return void
     */
    public function addTemplate(string $path, string $location): void
    {
        if (isset($this->ghosts[$path])) {
            throw new InvalidArgumentException('Template already exists.');
        }

        $this->ghosts[$path] = $location;
    }

    /**
     * @return array<\StaticServer\Modifier\ModifyInterface>
     */
    public function getModifiers(): array
    {
        return $this->modifiers;
    }

    /**
     * Method for modify incoming files
     * or add new one. Then, saves to disk without override original files.
     *
     * @param iterable<Transfer> $files
     *
     * @return void
     */
    public function modifyAndSaveToDisk(iterable $files): void
    {
        $modifyPath = $this->configuration->get('server.modify.root');

        $this->removeModifiedFilesIfExists($modifyPath);

        $results = [];
        $this->modifyGhostFiles($results);
        $this->modifyRealFiles($files, $results);

        $this->copyOriginalFiles($modifyPath);
        $this->putToDisk($results, $modifyPath);
    }

    /**
     * @param string $modifyPath
     */
    private function removeModifiedFilesIfExists(string $modifyPath): void
    {
        if ($this->filesystem->exists($modifyPath)) {
            $this->logger->debug(sprintf('Modify. Files by path [%s] found and will be deleted', $modifyPath));
            $this->filesystem->remove($modifyPath);
        }
    }

    /**
     * @param array<mixed> $results
     *
     * @return void
     */
    private function modifyGhostFiles(array &$results): void
    {
        foreach ($this->ghosts as $path => $location) {
            $file = new SplFileInfo($path);
            $contents = (string) file_get_contents($path);

            $item = new Transfer();
            $item->filename  = $file->getFilename();
            $item->realpath  = $file->getRealPath() ?: $path;
            $item->extension = $file->getExtension();
            $item->location  = $location;
            $item->content   = $contents;

            $this->logger->debug(sprintf('Modify. Starting modify the ghost file: %s', $path));

            $results[] = array_reduce($this->modifiers, function ($carry, ModifyInterface $handler) use ($item) {
                if ($handler instanceof ConfigurationAwareInterface) {
                    $handler->setConfiguration($this->configuration);
                }
                return $handler($carry ?: clone $item, $item);
            });
        }
    }

    /**
     * @param iterable<Transfer> $files
     * @param array<mixed> $results
     * @return void
     */
    private function modifyRealFiles(iterable $files, array &$results): void
    {
        foreach ($files as $item) {
            $results[] = array_reduce($this->modifiers, function ($carry, ModifyInterface $handler) use ($item) {
                if ($handler instanceof ConfigurationAwareInterface) {
                    $handler->setConfiguration($this->configuration);
                }
                return $handler($carry ?: clone $item, $item);
            });
        }
    }

    /**
     * @param array<Transfer> $modified
     * @param string $modifyPath
     */
    private function putToDisk(array $modified, string $modifyPath): void
    {
        foreach ($modified as $transfer) {
            /** @var Transfer $transfer */

            $path = $modifyPath . DIRECTORY_SEPARATOR . $transfer->location;
            $dir  = pathinfo($path, PATHINFO_DIRNAME);

            if (!$this->filesystem->exists($dir)) {
                $this->filesystem->mkdir($dir);
            }

            file_put_contents($path, $transfer->content);
        }
    }

    /**
     * @param string $modifyPath
     */
    private function copyOriginalFiles(string $modifyPath): void
    {
        $rootPath = $this->configuration->get('server.root');

        // If it exist, check if it's a directory
        if(!$rootPath || !is_dir($rootPath)) {
            throw new InvalidArgumentException('Config Error: server.root directory not found or it is not directory.');
        }

        $directoryIterator = new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $item) {
            /** @var RecursiveDirectoryIterator $item */
            /** @var RecursiveDirectoryIterator $iterator */

            $filename = $modifyPath . DIRECTORY_SEPARATOR . $iterator->getSubPathname();

            if ($item->isDir()) {
                $this->logger->debug(sprintf('Modify. Copy original file, create dir: %s', $filename));
                $this->filesystem->mkdir($filename);
            } else {
                $this->logger->debug(sprintf('Modify. Copy original file, copy file from: %s to %s', $item, $filename));
                $this->filesystem->copy($item, $filename);
            }
        }
    }
}
