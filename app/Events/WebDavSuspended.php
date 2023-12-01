<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;

class WebDavSuspended {
    use SerializesModels;

    public function __construct(public readonly User $user) {
    }
}
