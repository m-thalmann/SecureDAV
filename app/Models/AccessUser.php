<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessUser extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "user_id",
        "username",
        "password",
        "readonly",
        "access_all",
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ["password", "created_at", "updated_at"];

    public function user() {
        return $this->hasOne(User::class, "id", "user_id");
    }

    public function accessFiles() {
        return $this->belongsToMany(File::class, "access_user_files");
    }

    public function files() {
        if ($this->access_all) {
            return $this->user->files;
        } else {
            return $this->accessFiles;
        }
    }
}
