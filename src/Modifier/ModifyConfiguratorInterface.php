<?php declare(strict_types=1);

namespace StaticServer\Modifier;

interface ModifyConfiguratorInterface
{
    /**
     * Configures the concrete object which modify the files.
     *
     * @return \StaticServer\Modifier\GenericModifyInterface
     */
    public function getModifier(): GenericModifyInterface;
}
