<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebDavUser>
 */
class WebDavUserFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'username' => fake()->uuid(),
            'password' => Hash::make('password'),
            'label' => $this->faker->firstName(),
            'user_id' => User::factory(),
            'active' => true,
            'readonly' => $this->faker->boolean(),
            'last_access' => null,
        ];
    }
}
