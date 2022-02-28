<?php

namespace App\WebDav;

use App\Models\File;
use InvalidArgumentException;
use Sabre\DAV;

/**
 * Virtual directory implementation for the Sabre\DAV server.
 * Currently there can only exist the root-directory, which lists all files visible
 * for the authenticated user. It is also not possible to create a new file in the directory,
 * only replacing/updating is possible
 */
class VirtualDirectory extends DAV\Collection {
    private $path;
    private $children = null;

    function __construct($path) {
        $this->path = $path;

        if ($path !== "/") {
            throw new InvalidArgumentException(
                "The current implementation does not support subdirectories."
            );
        }
    }

    private function loadChildren() {
        $this->children = [];

        foreach (Authentication::getUser()->files() as $file) {
            if ($file instanceof File) {
                if ($file->getAmountVersions() === 0) {
                    continue;
                }
            }
            $this->children[] = new VirtualFile($file);
        }
    }

    function getChildren() {
        if ($this->children === null) {
            $this->loadChildren();
        }

        return $this->children;
    }

    function getChild($name) {
        // Some added security
        if ($name[0] == ".") {
            throw new DAV\Exception\NotFound("Access denied");
        }

        foreach ($this->getChildren() as $child) {
            if ($child instanceof VirtualFile) {
                if ($child->checkName($name)) {
                    return $child;
                }
            } else {
                if ($child->getName() === $name) {
                    return $child;
                }
            }
        }

        throw new DAV\Exception\NotFound(
            "The file with name: " . $name . " could not be found"
        );
    }

    function childExists($name) {
        try {
            $this->getChild($name);
        } catch (DAV\Exception\NotFound) {
            return false;
        }

        return true;
    }

    function getName() {
        return $this->path;
    }
}
