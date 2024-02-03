<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Storage;

class FileVersion extends Model {
    use HasFactory;

    protected $dateFormat = 'c';

    protected $hidden = ['storage_path'];

    protected $fillable = ['file_id', 'label', 'mime_type'];

    protected $casts = [
        'encryption_key' => 'encrypted',
        'file_updated_at' => 'datetime',
    ];

    public function scopeLatestVersion(Builder $query): Builder {
        return $query->where('version', function (QueryBuilder $query) {
            $query
                ->selectRaw('max("fv"."version")')
                ->from('file_versions as fv')
                ->whereColumn('fv.file_id', 'file_versions.file_id');
        });
    }

    public function file(): BelongsTo {
        return $this->belongsTo(File::class);
    }

    protected function isEncrypted(): Attribute {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $attributes['encryption_key'] !== null;
            }
        );
    }

    protected static function booted(): void {
        static::deleting(function (FileVersion $fileVersion) {
            Storage::disk('files')->delete($fileVersion->storage_path);
        });
    }
}

