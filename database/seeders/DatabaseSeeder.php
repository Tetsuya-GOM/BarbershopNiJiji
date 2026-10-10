<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\PaymentRecorder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create(['name' => 'System Administrator', 'email' => 'admin@barbershop.test', 'password' => Hash::make('password'), 'role' => 'admin', 'email_verified_at' => now()]);
        $barberNames = [['Miguel', 'Santos', 'Classic Cuts'], ['Carlo', 'Reyes', 'Skin Fades'], ['Paolo', 'Mendoza', 'Beard Styling'], ['Rafael', 'Cruz', 'Modern Styles'], ['Luis', 'Garcia', 'Hair Design']];
        $barbers = collect($barberNames)->map(function ($n, $i) {
            $email = strtolower($n[0]).'@barbershop.test';
            $u = User::create(['name' => "$n[0] $n[1]", 'email' => $email, 'password' => Hash::make('password'), 'role' => 'barber', 'email_verified_at' => now()]);

            return Barber::create(['user_id' => $u->id, 'first_name' => $n[0], 'last_name' => $n[1], 'contact_number' => '0917'.str_pad((string) (1200000 + $i), 7, '0', STR_PAD_LEFT), 'email' => $email, 'specialization' => $n[2], 'status' => 'active']);
        });
        $serviceRows = [['Regular Haircut', 'Clean classic haircut', 100, 30], ['Fade Haircut', 'Precision fade and finish', 150, 45], ['Beard Trim', 'Shape and detail beard', 80, 20], ['Haircut + Beard Trim', 'Complete cut and beard grooming', 180, 60], ['Hair Wash', 'Refreshing shampoo and rinse', 70, 20], ['Hair Styling', 'Professional styling and finish', 120, 30], ['Kids Haircut', 'Gentle haircut for children', 90, 30], ['Senior Haircut', 'Classic cut for seniors', 90, 30]];
        $services = collect($serviceRows)->map(fn ($s) => Service::create(['name' => $s[0], 'description' => $s[1], 'price' => $s[2], 'duration_minutes' => $s[3], 'status' => 'available']));
        $names = [['Juan', 'Dela Cruz'], ['Jose', 'Rizal'], ['Andres', 'Bonifacio'], ['Emilio', 'Aguinaldo'], ['Antonio', 'Luna'], ['Gabriel', 'Silang'], ['Diego', 'Silang'], ['Marcelo', 'Del Pilar'], ['Apolinario', 'Mabini'], ['Manuel', 'Quezon'], ['Lorenzo', 'Tan'], ['Nathaniel', 'Lim'], ['Jerome', 'Bautista'], ['Kenneth', 'Villanueva'], ['Vincent', 'Aquino'], ['Adrian', 'Castillo'], ['Joshua', 'Navarro'], ['Mark', 'Flores'], ['Christian', 'Ramos'], ['Daniel', 'Torres']];
        $customers = collect($names)->map(fn ($n, $i) => Customer::create(['first_name' => $n[0], 'last_name' => $n[1], 'contact_number' => '09'.str_pad((string) (171000000 + $i), 9, '0', STR_PAD_LEFT), 'email' => strtolower($n[0].'.'.$n[1]).'@example.test', 'address' => ($i + 1).' Mabini Street, Quezon City', 'is_active' => true]));
        foreach (['shop_name' => 'Barbershop ni Jiji', 'contact_number' => '09171234567', 'address' => '123 Rizal Avenue, Quezon City', 'opening_time' => '08:00', 'closing_time' => '19:00', 'operating_days' => 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday'] as $key => $value) {
            Setting::create(compact('key', 'value'));
        }
        $statuses = ['completed', 'completed', 'confirmed', 'pending', 'in_progress'];
        $appointments = collect();
        for ($i = 0; $i < 30; $i++) {
            $service = $services[$i % $services->count()];
            $day = now()->startOfDay()->addDays(($i % 12) - 6);
            while ($day->isSunday()) {
                $day->addDay();
            }$hour = 9 + intdiv($i % 15, 5) * 2;
            $start = sprintf('%02d:00', $hour);
            $appointments->push(Appointment::create(['customer_id' => $customers[$i % 20]->id, 'barber_id' => $barbers[$i % 5]->id, 'service_id' => $service->id, 'appointment_date' => $day->toDateString(), 'start_time' => $start, 'end_time' => $day->copy()->setTime($hour, 0)->addMinutes($service->duration_minutes)->format('H:i'), 'status' => $statuses[$i % 5], 'service_name_snapshot' => $service->name, 'service_price_snapshot' => $service->price, 'service_duration_snapshot' => $service->duration_minutes, 'notes' => $i % 4 === 0 ? 'Regular customer' : null]));
        }
        $recorder = app(PaymentRecorder::class);
        foreach ($appointments->where('status', 'completed')->take(10) as $i => $a) {
            $amount = (float) $a->service_price_snapshot;
            if ($i % 3 === 0) {
                $recorder->record($a, $amount / 2, $i % 2 ? 'gcash' : 'cash', $a->appointment_date->setTime(12, 0)->toDateTimeString());
            } else {
                $recorder->record($a, $amount, $i % 2 ? 'gcash' : 'cash', $a->appointment_date->setTime(12,0)->toDateTimeString());
            }
        }
    }
}
