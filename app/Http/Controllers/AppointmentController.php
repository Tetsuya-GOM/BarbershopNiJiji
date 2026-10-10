<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentRequest;
use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Customer;
use App\Models\Service;
use App\Services\AppointmentScheduler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $q = request('search');
        $appointments = Appointment::with(['customer', 'barber', 'payment'])->visibleTo($user)
            ->when($q, fn ($x) => $x->whereHas('customer', fn ($c) => $c->where('first_name', 'like', "%$q%")->orWhere('last_name', 'like', "%$q%")))
            ->when(request('date'), fn ($x, $v) => $x->whereDate('appointment_date', $v))->when(request('barber_id'), fn ($x, $v) => $x->where('barber_id', $v))
            ->when(request('status'), fn ($x, $v) => $x->where('status', $v))->orderByDesc('appointment_date')->orderByDesc('start_time')->paginate(12)->withQueryString();
        $barbers = Barber::active()->orderBy('first_name')->get();

        return view('appointments.index', compact('appointments', 'barbers'));
    }

    public function create()
    {
        return view('appointments.form', $this->options(new Appointment));
    }

    public function store(AppointmentRequest $r, AppointmentScheduler $scheduler)
    {
        $a = $scheduler->save($r->validated());

        return redirect()->route('appointments.show', $a)->with('success', 'Appointment scheduled.');
    }

    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);
        $appointment->load(['customer', 'barber', 'service', 'payment.transactions']);

        return view('appointments.show', compact('appointment'));
    }

    public function edit(Appointment $appointment)
    {
        $this->authorize('update', $appointment);
        abort_unless(request()->user()->isAdmin(), 403);

        return view('appointments.form', $this->options($appointment));
    }

    public function update(AppointmentRequest $r, Appointment $appointment, AppointmentScheduler $scheduler)
    {
        abort_unless($r->user()->isAdmin(), 403);
        if ($appointment->status === 'cancelled' && $r->input('status') === 'completed') {
            throw ValidationException::withMessages(['status' => 'A cancelled appointment cannot be completed directly.']);
        }$scheduler->save($r->validated(), $appointment);

        return redirect()->route('appointments.show', $appointment)->with('success', 'Appointment updated.');
    }

    public function status(Request $r, Appointment $appointment)
    {
        $this->authorize('update', $appointment);
        $status = $r->validate(['status' => 'required|in:pending,confirmed,in_progress,completed,cancelled'])['status'];
        $allowed = $r->user()->isAdmin() ? ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'] : match ($appointment->status) {
            'pending','confirmed' => ['in_progress'],'in_progress' => ['completed'],default => []
        };
        if (! in_array($status, $allowed, true) || ($appointment->status === 'cancelled' && $status === 'completed')) {
            throw ValidationException::withMessages(['status' => 'That status transition is not allowed.']);
        }$appointment->update(['status' => $status]);

        return back()->with('success', 'Appointment status updated.');
    }

    public function destroy(Appointment $appointment)
    {
        $this->authorize('delete', $appointment);
        if ($appointment->payment?->amount_paid > 0) {
            return back()->withErrors(['appointment' => 'Appointments with payment history cannot be removed.']);
        }$appointment->delete();

        return redirect()->route('appointments.index')->with('success', 'Appointment deleted.');
    }

    private function options(Appointment $appointment): array
    {
        return ['appointment' => $appointment, 'customers' => Customer::where('is_active', true)->orderBy('first_name')->get(), 'barbers' => Barber::active()->orderBy('first_name')->get(), 'services' => Service::available()->orderBy('name')->get()];
    }
}
