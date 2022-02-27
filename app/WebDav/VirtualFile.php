<?php

namespace App\WebDav;

use App\Models\File;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Sabre\DAV;

/**
 * Virtual file implementation for the Sabre\DAV server.
 * It uses the Storage to read and store the file.
 */
class VirtualFile extends DAV\File {
    /**
     * @var File
     */
    private $file;

    /**
     * @var File|null The selected file (set by a get()-call)
     */
    private static $selectedFile = null;

    function __construct(File $file) {
        $this->file = $file;
    }

    function getName() {
        return $this->file->uuid . "." . $this->file->extension;
    }

    function get() {
        self::$selectedFile = $this->file;

        if ($this->file->encrypted) {
            $fileContents = Storage::disk("files")->get($this->getFilePath());

            return Crypt::decrypt($fileContents);
        } else {
            return Storage::disk("files")->readStream($this->getFilePath());
        }
    }

    function getSize() {
        return Storage::disk("files")->size($this->getFilePath());
    }

    function getETag() {
        /**
         * @var FilesystemAdapter
         */
        $fileSystem = Storage::disk("files");

        return '"' . md5_file($fileSystem->path($this->getFilePath())) . '"';
    }

    function put($data) {
        // TODO: implement
        throw new \BadFunctionCallException("Not implemented");
    }

    /**
     * @return string The file's path
     */
    private function getFilePath() {
        return $this->file->getLastVersion()->path;
    }

    /**
     * Returns the selected file (set on get()-call)
     *
     * @see Server::getResponse()
     *
     * @return File|null
     */
    public static function getSelectedFile() {
        return self::$selectedFile;
    }
}
