<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return ['first_name' => fake()->firstName(), 'last_name' => fake()->lastName(), 'contact_number' => '09'.fake()->numerify('#########'), 'email' => fake()->unique()->safeEmail(), 'address' => fake()->address(), 'is_active' => true];
    }
}
