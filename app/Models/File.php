<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class File extends Model {
    use HasFactory, SoftDeletes, Prunable;

    protected $primaryKey = "uuid";
    protected $keyType = "string";
    public $incrementing = false;

    protected $fillable = [
        "display_name",
        "client_name",
        "mime_type",
        "extension",
        "encrypted",
    ];

    protected $hidden = [];

    protected $attributes = [
        "encrypted" => true,
    ];

    public function versions() {
        return $this->hasMany(FileVersion::class)->orderBy("version", "desc");
    }

    /**
     * @return FileVersion|null
     */
    public function getLastVersion() {
        $count = count($this->versions);
        if ($count === 0) {
            return null;
        }

        return $this->versions[$count - 1];
    }

    /**
     * @return FileVersion|null
     */
    public function getVersion(int $version) {
        return FileVersion::whereHas("file", function ($query) use ($version) {
            return $query
                ->where("file_uuid", $this->uuid)
                ->where("version", $version);
        })->first();
    }

    public function getAmountVersions() {
        return count($this->versions);
    }

    /**
     * Creates a new file and its first version
     *
     * @param integer $userId The users id
     * @param \Illuminate\Http\UploadedFile $uploadedFile The uploaded file
     * @param boolean $encrypted Whether the file should be encrypted on the server
     * @param string|null $name The name to set for the file (uses upload name if null)
     *
     * @return File The created and saved file-object
     */
    public static function upload(
        $userId,
        $uploadedFile,
        $encrypted,
        $name = null
    ) {
        $file = new File();
        $file->fill([
            "display_name" => $name ?? $uploadedFile->getClientOriginalName(),
            "client_name" => $uploadedFile->getClientOriginalName(),
            "mime_type" => $uploadedFile->getClientMimeType(),
            "extension" => Str::lower(
                $uploadedFile->getClientOriginalExtension()
            ),
            "encrypted" => $encrypted,
        ]);
        $file->user_id = $userId;
        $file->save();

        try {
            FileVersion::createFirst($file->uuid, $uploadedFile, $encrypted);
        } catch (Exception $e) {
            $file->forceDelete();
            throw $e;
        }

        return $file;
    }

    /**
     * Uploads the new file to this file
     *
     * @param \Illuminate\Http\UploadedFile $uploadedFile The uploaded file
     * @param bool $newVersion Whether to create a new version or to replace the latest one
     *
     * @throws InvalidArgumentException When the mime-types or extensions do not match
     */
    public function uploadFile($file, $newVersion) {
        $mimeType = $file->getClientMimeType();
        $extension = Str::lower($file->getClientOriginalExtension());

        if ($mimeType !== $this->mime_type || $extension !== $this->extension) {
            throw new InvalidArgumentException();
        }

        if ($newVersion || $this->getAmountVersions() === 0) {
            FileVersion::createVersionFromFile(
                $this->uuid,
                $file,
                $this->encrypted
            );
        } else {
            $this->getLastVersion()->replaceFile($file, $this->encrypted);
        }
    }

    public function prunable() {
        return static::where("deleted_at", "<", now()->subDays(30));
    }

    protected function pruning() {
        $this->deleteFiles(true);
    }

    public function deleteFiles($withTrashed = false) {
        $paths = FileVersion::getFilePaths($this->uuid, $withTrashed);

        if (count($paths) > 0) {
            Storage::disk("files")->delete($paths);
        }
    }

    protected static function boot() {
        parent::boot();

        static::creating(function (Model $model) {
            $model->setAttribute($model->getKeyName(), Str::uuid());
        });
    }

    // TODO: add health-checks -> remove files from db that don't exist anymore + notify user
}
