<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FileVersion extends Model {
    use HasFactory, SoftDeletes;

    protected $hidden = ['storage_path'];

    protected $fillable = ['file_id', 'label'];

    public function scopeLatestVersion(Builder $query): Builder {
        return $query->orderBy('version', 'desc')->limit(1);
    }

    public function file(): BelongsTo {
        return $this->belongsTo(File::class);
    }
}

