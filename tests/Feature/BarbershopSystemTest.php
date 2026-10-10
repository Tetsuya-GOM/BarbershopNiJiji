<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Customer;
use App\Models\PaymentTransaction;
use App\Models\Service;
use App\Models\User;
use App\Services\PaymentRecorder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BarbershopSystemTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'password' => Hash::make('password')]);
    }

    private function domain(): array
    {
        $customer = Customer::factory()->create();
        $barber = Barber::factory()->create();
        $service = Service::factory()->create(['duration_minutes' => 30, 'price' => 150]);

        return compact('customer', 'barber', 'service');
    }

    private function appointment(array $overrides = []): Appointment
    {
        $d = $this->domain();
        $date = Carbon::now()->next(Carbon::MONDAY)->toDateString();

        return Appointment::create(array_merge(['customer_id' => $d['customer']->id, 'barber_id' => $d['barber']->id, 'service_id' => $d['service']->id, 'appointment_date' => $date, 'start_time' => '09:00', 'end_time' => '09:30', 'status' => 'confirmed', 'service_name_snapshot' => $d['service']->name, 'service_price_snapshot' => $d['service']->price, 'service_duration_snapshot' => 30], $overrides));
    }

    public function test_administrator_can_login(): void
    {
        $admin = $this->admin();
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_barber_cannot_access_admin_modules(): void
    {
        $user = User::factory()->create(['role' => 'barber']);
        Barber::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user)->get('/customers')->assertForbidden();
        $this->actingAs($user)->get('/appointments')->assertOk();
    }

    public function test_customer_crud(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/customers', ['first_name' => 'Ana', 'last_name' => 'Santos', 'contact_number' => '09171234567', 'email' => 'ana@test.local'])->assertRedirect();
        $c = Customer::first();
        $this->actingAs($admin)->put("/customers/$c->id", ['first_name' => 'Ana Marie', 'last_name' => 'Santos', 'contact_number' => '09171234567', 'email' => 'ana@test.local'])->assertRedirect();
        $this->assertDatabaseHas('customers', ['first_name' => 'Ana Marie']);
        $this->actingAs($admin)->delete("/customers/$c->id")->assertRedirect();
        $this->assertDatabaseMissing('customers', ['id' => $c->id]);
    }

    public function test_barber_crud_and_deactivation(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/barbers', ['first_name' => 'Marco', 'last_name' => 'Reyes', 'contact_number' => '09181234567', 'email' => 'marco@test.local', 'specialization' => 'Fades', 'status' => 'active'])->assertRedirect();
        $b = Barber::first();
        $this->actingAs($admin)->put("/barbers/$b->id", ['first_name' => 'Marco', 'last_name' => 'Reyes', 'contact_number' => '09181234567', 'email' => 'marco@test.local', 'specialization' => 'Classic Fades', 'status' => 'active'])->assertRedirect();
        $this->actingAs($admin)->delete("/barbers/$b->id")->assertRedirect();
        $this->assertDatabaseHas('barbers', ['id' => $b->id, 'status' => 'inactive']);
    }

    public function test_service_crud(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/services', ['name' => 'Executive Cut', 'price' => 250, 'duration_minutes' => 45, 'status' => 'available'])->assertRedirect();
        $s = Service::first();
        $this->actingAs($admin)->put("/services/$s->id", ['name' => 'Executive Cut', 'price' => 275, 'duration_minutes' => 45, 'status' => 'available'])->assertRedirect();
        $this->assertDatabaseHas('services', ['id' => $s->id, 'price' => 275]);
        $this->actingAs($admin)->delete("/services/$s->id")->assertRedirect();
        $this->assertDatabaseMissing('services', ['id' => $s->id]);
    }

    public function test_appointment_creation_and_duration_calculation(): void
    {
        $admin = $this->admin();
        $d = $this->domain();
        $date = Carbon::now()->next(Carbon::MONDAY)->toDateString();
        $this->actingAs($admin)->post('/appointments', ['customer_id' => $d['customer']->id, 'barber_id' => $d['barber']->id, 'service_id' => $d['service']->id, 'appointment_date' => $date, 'start_time' => '10:00', 'status' => 'confirmed'])->assertRedirect();
        $this->assertDatabaseHas('appointments', ['start_time' => '10:00:00', 'end_time' => '10:30:00', 'service_price_snapshot' => 150]);
    }

    public function test_overlapping_appointment_is_rejected(): void
    {
        $admin = $this->admin();
        $a = $this->appointment();
        $this->actingAs($admin)->from('/appointments/create')->post('/appointments', ['customer_id' => $a->customer_id, 'barber_id' => $a->barber_id, 'service_id' => $a->service_id, 'appointment_date' => $a->appointment_date->format('Y-m-d'), 'start_time' => '09:15', 'status' => 'confirmed'])->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_appointment_can_be_rescheduled_without_self_conflict(): void
    {
        $admin = $this->admin();
        $a = $this->appointment();
        $this->actingAs($admin)->put("/appointments/$a->id", ['customer_id' => $a->customer_id, 'barber_id' => $a->barber_id, 'service_id' => $a->service_id, 'appointment_date' => $a->appointment_date->format('Y-m-d'), 'start_time' => '11:00', 'status' => 'confirmed'])->assertRedirect();
        $this->assertDatabaseHas('appointments', ['id' => $a->id, 'start_time' => '11:00:00']);
    }

    public function test_barber_can_only_follow_valid_status_workflow(): void
    {
        $a = $this->appointment();
        $user = User::factory()->create(['role' => 'barber']);
        $a->barber->update(['user_id' => $user->id]);
        $this->actingAs($user)->patch("/appointments/$a->id/status", ['status' => 'completed'])->assertSessionHasErrors('status');
        $this->actingAs($user)->patch("/appointments/$a->id/status", ['status' => 'in_progress'])->assertRedirect();
        $this->actingAs($user)->patch("/appointments/$a->id/status", ['status' => 'completed'])->assertRedirect();
        $this->assertDatabaseHas('appointments', ['id' => $a->id, 'status' => 'completed']);
    }

    public function test_partial_payment_and_overpayment_protection(): void
    {
        $a = $this->appointment();
        $recorder = app(PaymentRecorder::class);
        $p = $recorder->record($a, 50, 'cash');
        $this->assertEquals(100, (float) $p->balance);
        $this->expectException(ValidationException::class);
        $recorder->record($a, 101, 'gcash');
    }

    public function test_revenue_report_uses_actual_transactions(): void
    {
        $admin = $this->admin();
        $a = $this->appointment(['status' => 'completed']);
        app(PaymentRecorder::class)->record($a, 75, 'gcash');
        $this->actingAs($admin)->get('/reports?from='.now()->subMonth()->toDateString().'&to='.now()->addMonth()->toDateString())->assertOk()->assertSee('75.00');
        $this->assertEquals(75, (float) PaymentTransaction::sum('amount'));
    }

    public function test_administrator_screens_render(): void
    {
        $admin = $this->admin();
        $a = $this->appointment();
        $p = app(PaymentRecorder::class)->record($a, 50, 'cash');
        foreach (['/dashboard', '/customers', '/customers/create', "/customers/$a->customer_id", '/barbers', '/barbers/create', "/barbers/$a->barber_id", '/services', '/services/create', "/services/$a->service_id", '/appointments', '/appointments/create', "/appointments/$a->id", "/appointments/$a->id/edit", '/payments', '/payments/create', "/payments/$p->id", "/payments/$p->id/receipt", '/reports', '/settings', '/users'] as $uri) {
            $this->actingAs($admin)->get($uri)->assertOk();
        }
    }
}
