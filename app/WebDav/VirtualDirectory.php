<?php

namespace App\WebDav;

use InvalidArgumentException;
use Sabre\DAV;

/**
 * Virtual directory implementation for the Sabre\DAV server.
 * Currently there can only exist the root-directory, which lists
 * all files visible for the authenticated user.
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

    function getChildren() {
        if ($this->children === null) {
            foreach (Authentication::getUser()->files() as $file) {
                $this->children[] = new VirtualFile($file);
            }
        }

        return $this->children;
    }

    function getChild($name) {
        // Some added security
        if ($name[0] == ".") {
            throw new DAV\Exception\NotFound("Access denied");
        }

        foreach ($this->getChildren() as $child) {
            if ($child->getName() === $name) {
                return $child;
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

    function createFile($name, $data = null) {
        // TODO: implement
        throw new \BadFunctionCallException("Not implemented");
    }
}
