<?php

namespace App\Models;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileVersion extends Model {
    use HasFactory, SoftDeletes, Prunable;

    const MAX_PATH_TRIES = 10;

    protected $fillable = [];

    protected $hidden = ["path"];

    protected $attributes = [];

    public function file() {
        return $this->hasOne(File::class, "uuid", "file_uuid");
    }

    /**
     * Creates the first version of a file
     *
     * @param string $fileUuid The file's uuid
     * @param \Illuminate\Http\UploadedFile $file The uploaded file
     * @param boolean $doEncrypt Whether the file should be encrypted
     *
     * @return FileVersion The created and saved file-version
     */
    public static function createFirst($fileUuid, $file, $doEncrypt) {
        return self::createVersionFromFile($fileUuid, $file, $doEncrypt);
    }

    /**
     * Creates a new version of an existing file by its id
     *
     * @param string $fileUuid The uuid of the file
     *
     * @throws Exception If the version could not be created
     *
     * @return FileVersion|null The created and saved file-version (or null if no older version)
     */
    public static function createVersion($fileUuid) {
        $file = FileVersion::where("file_uuid", $fileUuid)
            ->withTrashed()
            ->orderBy("version", "desc")
            ->limit(1)
            ->first();

        if ($file === null) {
            return null;
        }

        $version = new FileVersion();
        $version->file_uuid = $fileUuid;
        $version->version = $file->version + 1;
        $version->path = self::generatePath();
        $version->etag = $file->etag;
        $version->bytes = $file->bytes;

        try {
            if (!Storage::disk("files")->copy($file->path, $version->path)) {
                throw new Exception("Version could not be created");
            }
        } catch (FileNotFoundException $e) {
            throw new Exception(
                "File of previous version could not be found. Try to delete the previous version"
            );
        }

        if (!$version->save()) {
            Storage::disk("files")->delete($version->path);
            throw new Exception("Version could not be saved");
        }

        return $version;
    }

    /**
     * Creates a new version of a file using an uploaded file
     *
     * @param string $fileUuid The file's uuid
     * @param \Illuminate\Http\UploadedFile $file The uploaded file
     * @param boolean $doEncrypt Whether the file should be encrypted
     *
     * @return FileVersion The created and saved file-version
     */
    public static function createVersionFromFile($fileUuid, $file, $doEncrypt) {
        $fileVersion = FileVersion::where("file_uuid", $fileUuid)
            ->withTrashed()
            ->orderBy("version", "desc")
            ->limit(1)
            ->first();

        $lastVersion = 0;

        if ($fileVersion !== null) {
            $lastVersion = $fileVersion->version;
        }

        $version = new FileVersion();
        $version->file_uuid = $fileUuid;
        $version->version = $lastVersion + 1;
        $version->path = self::generatePath();

        list($etag, $bytes) = self::putFile(
            $version->path,
            $file,
            $doEncrypt,
            false
        );

        $version->etag = $etag;
        $version->bytes = $bytes;

        if (!$version->save()) {
            Storage::disk("files")->delete($version->path);
            throw new Exception("Version could not be saved");
        }

        return $version;
    }

    /**
     * Returns a streaming-download-response for the file
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download() {
        $fileContents = Storage::disk("files")->get($this->path);

        if (!$fileContents) {
            throw new Exception("File not found");
        }

        if ($this->file->encrypted) {
            $fileContents = Crypt::decrypt($fileContents);
        }

        $headers = [
            "Content-Type" => $this->file->mime_type,
            "Content-Disposition" =>
                "attachment; filename=" . $this->file->client_name,
        ];

        return response()->stream(
            function () use ($fileContents) {
                echo $fileContents;
            },
            200,
            $headers
        );
    }

    /**
     * Replaces the version's file and updates it's updated_at-timestamp
     *
     * @param \Illuminate\Http\UploadedFile|resource $file The uploaded file or resource to store
     * @param bool $doEncrypt Whether the file should be encrypted (derived from the File-instance)
     * @param bool $useTmp Whether the file should be uploaded to a temporary file and then moved to the correct path
     */
    public function replaceFile($file, $doEncrypt, $useTmp = true) {
        list($etag, $bytes) = self::putFile(
            $this->path,
            $file,
            $doEncrypt,
            $useTmp
        );

        $this->etag = $etag;
        $this->bytes = $bytes;

        $this->touch();
    }

    public function prunable() {
        return static::where("deleted_at", "<=", now()->subDays(30));
    }

    protected function pruning() {
        $this->deleteFile();
    }

    /**
     * Deletes the file for this version from the storage
     */
    public function deleteFile() {
        Storage::disk("files")->delete($this->path);
    }

    /**
     * Returns the size of the version is on the disk.
     * If not encrypted, will be the same as 'bytes'.
     *
     * @return number
     */
    public function getBytesOnDisk() {
        return Storage::disk("files")->size($this->path);
    }

    /**
     * Returns all file-paths for the given file-uuid
     *
     * @param string $fileUuid The files uuid
     * @param bool $withTrashed Whether to include the trashed versions as well
     *
     * @return string[]
     */
    public static function getFilePaths($fileUuid, $withTrashed = false) {
        if ($withTrashed) {
            $query = static::withTrashed();
        } else {
            $query = static::query();
        }

        return $query
            ->where("file_uuid", $fileUuid)
            ->pluck("path")
            ->toArray();
    }

    /**
     * Stores a file to the given path
     *
     * @param string $path The path within the "files"-storage
     * @param \Illuminate\Http\UploadedFile|resource $file The uploaded file or resource to store
     * @param bool $doEncrypt Whether the file should be encrypted (derived from the File-instance)
     * @param bool $useTmp Whether the file should be uploaded to a temporary file and then moved to the correct path
     *
     * @return array Returns the etag and filesize of the stored file:
     *               - `[0]` - The ETag
     *               - `[1]` - The file size in bytes
     */
    private static function putFile($path, $file, $doEncrypt, $useTmp = true) {
        $uploadPath = $path;

        if ($useTmp) {
            $uploadPath .= ".tmp";
        }

        $etag = $path;
        $bytes = 0;

        if ($doEncrypt) {
            if (is_resource($file)) {
                $contents = stream_get_contents($file);
            } else {
                $contents = file_get_contents($file->getRealPath());
            }

            $etag = md5($contents);
            $bytes = strlen($contents);

            $encrypted = Crypt::encrypt($contents);
            Storage::disk("files")->put($uploadPath, $encrypted);
        } else {
            if (is_resource($file)) {
                Storage::disk("files")->put($uploadPath, $file);
            } else {
                $file->storeAs("", $uploadPath, "files");
            }

            /**
             * @var FilesystemAdapter
             */
            $fileSystem = Storage::disk("files");

            $etag = md5_file($fileSystem->path($path));
            $bytes = $fileSystem->size($path);
        }

        if ($useTmp) {
            Storage::disk("files")->move($uploadPath, $path);
        }

        return [$etag, $bytes];
    }

    /**
     * Generates a random path for a file.
     * If a file with this path already exists, it retries the operation MAX_PATH_TRIES times
     *
     * @throws Exception If there could not be found any unused path within MAX_PATH_TRIES tries
     *
     * @return string The path
     */
    private static function generatePath() {
        $pathTries = 0;
        $exists = false;

        do {
            $path = Str::uuid()->toString();
        } while (
            $exists =
                Storage::disk("files")->exists($path) &&
                $pathTries++ < self::MAX_PATH_TRIES
        );

        if ($exists) {
            throw new Exception("No unused file-path found");
        }

        return $path;
    }
}
