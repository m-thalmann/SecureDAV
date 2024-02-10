<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;

class UserDeleted {
    use SerializesModels;

    public readonly array $userData;

    public function __construct(User $user) {
        $this->userData = $user->only(['id', 'name', 'email']);
    }
}
