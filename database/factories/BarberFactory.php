<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BarberFactory extends Factory
{
    public function definition(): array
    {
        return ['first_name' => fake()->firstNameMale(), 'last_name' => fake()->lastName(), 'contact_number' => '09'.fake()->numerify('#########'), 'email' => fake()->unique()->safeEmail(), 'specialization' => fake()->randomElement(['Classic Cuts', 'Fades', 'Beard Styling']), 'status' => 'active'];
    }
}
