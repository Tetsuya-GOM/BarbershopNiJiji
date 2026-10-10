<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->words(2, true), 'description' => fake()->sentence(), 'price' => fake()->randomElement([80, 100, 120, 150, 180]), 'duration_minutes' => fake()->randomElement([20, 30, 45, 60]), 'status' => 'available'];
    }
}
