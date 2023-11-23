<?php

namespace Database\Factories;

use App\Models\AccessGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AccessGroupUser>
 */
class AccessGroupUserFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'username' => fake()->uuid(),
            'access_group_id' => AccessGroup::factory(),
            'password' => Hash::make('password'),
            'label' => strtolower($this->faker->firstName()),
            'last_access' => null,
        ];
    }
}

