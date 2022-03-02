<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class AccessUser extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ["user_id", "username", "readonly", "access_all"];

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

    public function tokens() {
        return $this->hasMany(AccessUserToken::class);
    }

    public function activeTokens() {
        return $this->tokens()->where("active", true);
    }

    public function files() {
        if ($this->access_all) {
            return $this->user->files;
        } else {
            return $this->accessFiles;
        }
    }

    /**
     * Authenticates the user using the given token.
     * If a token matches with it, it's last_access-timestamp is set to now
     * and true is returned
     *
     * @param string $loginToken The token from the login
     *
     * @return bool Whether the authentication was successful
     */
    public function authenticate($loginToken) {
        $tokens = $this->activeTokens()->get();

        foreach ($tokens as $token) {
            if (Hash::check($loginToken, $token->token)) {
                $token->last_access = Carbon::now();
                $token->save();

                return true;
            }
        }

        return false;
    }

    /**
     * Returns the timestamp, when the user was last used
     * to access a file
     *
     * @return Carbon|null The timestamp as a Carbon-instance or null if never accessed
     */
    public function lastAccess() {
        $access = $this->activeTokens()
            ->whereNotNull("last_access")
            ->orderBy("last_access", "desc")
            ->limit(1)
            ->pluck("last_access")
            ->toArray();

        if (count($access) > 0) {
            return Carbon::parse($access[0]);
        }

        return null;
    }
}
