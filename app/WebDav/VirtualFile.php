<?php

namespace App\WebDav;

use App\Models\File;
use App\Models\FileVersion;
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
     * @var FileVersion
     */
    private $version;

    /**
     * @var File|null The selected file (set by a get()-call)
     */
    private static $selectedFile = null;

    function __construct(File $file) {
        $this->file = $file;
        $this->version = $file->getLastVersion();
    }

    function getName() {
        return $this->file->uuid . "." . $this->file->extension;
    }

    /**
     * Checks whether the name matches the uuid or "<uuid>.<extension>"
     *
     * @param string $name The name to check
     *
     * @return bool Whether the names match
     */
    function checkName($name) {
        return $this->file->uuid === $name || $this->getName() === $name;
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
        return $this->version->bytes;
    }

    function getETag() {
        return '"' . $this->version->etag . '"';
    }

    function put($data) {
        if (Authentication::getUser()->readonly) {
            throw new DAV\Exception\Forbidden("You are a read-only user.");
        }

        $this->version->replaceFile($data, $this->file->encrypted);

        return '"' . $this->version->etag . '"';
    }

    /**
     * @return string The file's path
     */
    private function getFilePath() {
        return $this->version->path;
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
