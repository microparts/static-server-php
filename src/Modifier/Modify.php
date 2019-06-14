<?php declare(strict_types=1);

namespace StaticServer\Modifier;

use SplFileInfo;
use StaticServer\Transfer;

final class Modify implements GenericModifyInterface
{
    /**
     * @var array
     */
    private $modifiers = [];

    /**
     * @var Transfer[]
     */
    private $ghosts = [];

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
    public function addGhostFile(string $path, string $location): void
    {
        $file = new SplFileInfo($path);
        $contents = file_get_contents($file->getRealPath());

        $this->ghosts[] = new Transfer(
            $file->getFilename(),
            $file->getRealPath(),
            $file->getExtension(),
            $location,
            $contents
        );
    }

    /**
     * @return array
     */
    public function getModifiers(): array
    {
        return $this->modifiers;
    }

    /**
     * @param iterable $files
     * @return iterable
     */
    public function modify(iterable $files): iterable
    {
        foreach ($this->ghosts as $item) {
            yield array_reduce($this->modifiers, function ($carry, ModifyInterface $handler) use ($item) {
                return $handler($carry ?: clone $item, $item);
            });
        }

        foreach ($files as $item) {
            yield array_reduce($this->modifiers, function ($carry, ModifyInterface $handler) use ($item) {
                return $handler($carry ?: clone $item, $item);
            });
        }
    }
}
