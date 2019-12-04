<?php declare(strict_types=1);

namespace StaticServer\Modifier;

use InvalidArgumentException;
use Microparts\Configuration\ConfigurationAwareInterface;
use Microparts\Configuration\ConfigurationAwareTrait;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use StaticServer\Modifier\Iterator\Transfer;
use Symfony\Component\Filesystem\Filesystem;

final class Modify implements GenericModifyInterface, ConfigurationAwareInterface
{
    use ConfigurationAwareTrait;

    /**
     * @var array
     */
    private array $modifiers = [];

    /**
     * @var Transfer[]
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
     * @param $handler
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
     * @return array
     */
    public function getModifiers(): array
    {
        return $this->modifiers;
    }

    /**
     * Method for modify incoming files
     * or add new one. Then, saves to disk without override original files.
     *
     * @param iterable $files
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
            $this->filesystem->remove($modifyPath);
        }
    }

    /**
     * @param array $results
     *
     * @return void
     */
    private function modifyGhostFiles(array &$results): void
    {
        foreach ($this->ghosts as $path => $location) {
            $file = new SplFileInfo($path);
            $contents = file_get_contents($path);

            $item = new Transfer();
            $item->filename  = $file->getFilename();
            $item->realpath  = $file->getRealPath() ?: $path;
            $item->extension = $file->getExtension();
            $item->location  = $location;
            $item->content   = $contents;

            $results[] = array_reduce($this->modifiers, function ($carry, ModifyInterface $handler) use ($item) {
                if ($handler instanceof ConfigurationAwareInterface) {
                    $handler->setConfiguration($this->configuration);
                }
                return $handler($carry ?: clone $item, $item);
            });
        }
    }

    /**
     * @param iterable $files
     * @param array $results
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
     * @param array $modified
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
     * @param $modifyPath
     */
    private function copyOriginalFiles(string $modifyPath): void
    {
        $rootPath = $this->configuration->get('server.root');

        // If it exist, check if it's a directory
        if(!$rootPath || !is_dir($rootPath)) {
            throw new InvalidArgumentException('Config Error: server.root directory not found or it is not directory.');
        }

        $directoryIterator = new RecursiveDirectoryIterator($rootPath);
        $iterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $item) {
            /** @var RecursiveDirectoryIterator $item */
            /** @var RecursiveDirectoryIterator $iterator */
            if ($item->isDir()) {
                $this->filesystem->mkdir($modifyPath . DIRECTORY_SEPARATOR . $iterator->getSubPathname());
            } else {
                $this->filesystem->copy($item, $modifyPath . DIRECTORY_SEPARATOR . $iterator->getSubPathname());
            }
        }
    }
}
