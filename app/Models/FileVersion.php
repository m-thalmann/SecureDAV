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

        self::putFile($version->path, $file, $doEncrypt);

        if (!$version->save()) {
            Storage::disk("files")->delete($version->path);
            throw new Exception("Version could not be saved");
        }

        return $version;
    }

    public function replaceFile($file, $doEncrypt) {
        self::putFile($this->path, $file, $doEncrypt);
        $this->touch();
    }

    public static function putFile($path, $file, $doEncrypt) {
        if ($doEncrypt) {
            $encrypted = Crypt::encrypt(
                file_get_contents($file->getRealPath())
            );
            Storage::disk("files")->put($path, $encrypted);
        } else {
            $file->storeAs("", $path, "files");
        }
    }

    public function prunable() {
        return static::where("deleted_at", "<=", now()->subDays(30));
    }

    protected function pruning() {
        $this->deleteFile();
    }

    public function deleteFile() {
        Storage::disk("files")->delete($this->path);
    }

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

    // TODO: if deleted -> delete corresponding file; also check if migrate rollback
    // TODO: add command to clean files
}
