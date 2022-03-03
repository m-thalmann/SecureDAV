<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessUserToken extends Model {
    use HasFactory;

    protected $fillable = [];

    protected $hidden = ["token", "last_access"];

    public $timestamps = false;

    public function accessUser() {
        return $this->belongsTo(AccessUser::class);
    }
}
