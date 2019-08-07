<?php declare(strict_types=1);

namespace StaticServer\Generic;

interface PrepareInterface
{
    /**
     * Prepare some data before accept server requests.
     *
     * @return void
     */
    public function prepare(): void;
}
