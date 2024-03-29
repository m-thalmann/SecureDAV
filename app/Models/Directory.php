<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Directory extends Model {
    use HasFactory, HasUuids;

    protected $fillable = ['parent_directory_id', 'name'];

    protected ?array $computedBreadcrumbs = null;

    public function uniqueIds(): array {
        return ['uuid'];
    }

    public function scopeForUser(Builder $query, User $user): Builder {
        return $query->where('user_id', $user->id);
    }

    public function scopeInDirectory(
        Builder $query,
        ?Directory $directory,
        bool $filterUser = true
    ): Builder {
        if ($directory === null) {
            return $query
                ->whereNull('parent_directory_id')
                ->when(
                    $filterUser,
                    fn(Builder $query) => $query->forUser(authUser())
                );
        }

        return $query->where('parent_directory_id', $directory->id);
    }

    public function scopeOrdered(
        Builder $query,
        string $direction = 'asc'
    ): Builder {
        return $query->orderBy('name', $direction);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function parentDirectory(): BelongsTo {
        return $this->belongsTo(Directory::class, 'parent_directory_id');
    }

    public function directories(): HasMany {
        return $this->hasMany(
            Directory::class,
            'parent_directory_id'
        )->ordered();
    }

    public function files(): HasMany {
        return $this->hasMany(File::class)->ordered();
    }

    protected function breadcrumbs(): Attribute {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                if ($this->computedBreadcrumbs !== null) {
                    return $this->computedBreadcrumbs;
                }

                $breadcrumbs = [];

                $directory = $this;

                while ($directory) {
                    array_unshift($breadcrumbs, $directory);

                    $directory = $directory->parentDirectory;
                }

                return $this->computedBreadcrumbs = $breadcrumbs;
            }
        );
    }

    protected function isEmpty(): Attribute {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $this->directories()->count() === 0 &&
                    $this->files()->count() === 0;
            }
        );
    }

    protected function webdavUrl(): Attribute {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return route('webdav.directories', [
                    collect($this->breadcrumbs)
                        ->map(fn(Directory $directory) => $directory->name)
                        ->join('/'),
                ]);
            }
        );
    }
}
