<?php

namespace Database\Factories;

use App\Models\Barber;
use App\Models\Customer;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $s = Service::factory()->create();
        $start = fake()->randomElement(['09:00', '10:00', '13:00', '14:00']);

        return ['customer_id' => Customer::factory(), 'barber_id' => Barber::factory(), 'service_id' => $s->id, 'appointment_date' => now()->addDays(2)->toDateString(), 'start_time' => $start, 'end_time' => now()->setTimeFromTimeString($start)->addMinutes($s->duration_minutes)->format('H:i'), 'status' => 'confirmed', 'service_name_snapshot' => $s->name, 'service_price_snapshot' => $s->price, 'service_duration_snapshot' => $s->duration_minutes];
    }
}
