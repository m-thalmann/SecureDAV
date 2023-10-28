<?php

namespace App\WebDav\Filesystem;

use Sabre\DAV;

/**
 * Abstract implementation of a virtual directory
 */
abstract class AbstractVirtualDirectory extends DAV\Collection {
    const BASE_NAME = '/';

    protected ?array $children = null;

    public function getChildren(): array {
        if ($this->children === null) {
            $this->children = $this->loadChildren();
        }

        return $this->children;
    }

    /**
     * Loads the children for this directory.
     * @return array<\App\WebDav\Filesystem\AbstractVirtualDirectory|\App\WebDav\Filesystem\VirtualFile>
     */
    abstract protected function loadChildren(): array;
}
