<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessUserToken extends Model {
    use HasFactory;

    protected $fillable = [];

    protected $hidden = ["token", "last_access"];

    protected $dates = ["last_access", "created_at"];

    public $timestamps = false;

    public function accessUser() {
        return $this->belongsTo(AccessUser::class);
    }

    public static function boot() {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }
}
