<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccessUser extends Model {
    use HasFactory;

    const MAX_USERNAME_TRIES = 10;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "user_id",
        "username",
        "label",
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

    public function tokens() {
        return $this->hasMany(AccessUserToken::class);
    }

    public function getActiveTokens() {
        return $this->tokens()->where("active", true);
    }

    /**
     * Returns the files, the user has access to
     */
    public function getFiles() {
        if ($this->access_all) {
            return File::query()
                ->where("user_id", $this->user_id)
                ->orWhereRelation("accessUsers", "access_users.id", $this->id);
        } else {
            return $this->accessFiles();
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
        $tokens = $this->getActiveTokens()->get();

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
    public function getLastAccess() {
        $access = $this->getActiveTokens()
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

    /**
     * Generates a random token (password) for this access-user
     * and returns the plain-text-token
     *
     * @return string
     */
    public function generateToken() {
        $token = Str::random(32);

        $accessToken = new AccessUserToken();
        $accessToken->access_user_id = $this->id;
        $accessToken->token = Hash::make($token);

        $accessToken->save();

        return $token;
    }

    /**
     * Generates a random username and returns it
     *
     * @throws Exception If no unused username could be found within MAX_USERNAME_TRIES tries
     *
     * @return string
     */
    public static function generateUsername() {
        $tries = 0;
        $exists = false;

        do {
            $username = Str::random(8);
            $exists =
                DB::table("access_users")
                    ->where("username", $username)
                    ->count() !== 0;
        } while ($exists && $tries++ < self::MAX_USERNAME_TRIES);

        if ($exists) {
            throw new Exception("No unused username found");
        }

        return $username;
    }
}
