<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;

class WebDavResumed {
    use SerializesModels;

    public function __construct(public readonly User $user) {
    }
}
