<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Models\Appointment;
use App\Models\Payment;
use App\Services\PaymentRecorder;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with(['appointment.customer', 'appointment.barber'])->when(request('status'), fn ($q, $v) => $q->where('payment_status', $v))->when(request('date_from'), fn ($q, $v) => $q->whereHas('transactions', fn ($t) => $t->whereDate('paid_at', '>=', $v)))->latest()->paginate(12)->withQueryString();

        return view('payments.index', compact('payments'));
    }

    public function create()
    {
        $appointments = Appointment::with(['customer', 'payment'])->where('status', '!=', 'cancelled')->orderByDesc('appointment_date')->get()->filter(fn ($a) => (float) ($a->payment?->balance ?? $a->service_price_snapshot) > 0);

        return view('payments.form', compact('appointments'));
    }

    public function store(PaymentRequest $r, PaymentRecorder $recorder)
    {
        $a = Appointment::findOrFail($r->appointment_id);
        $p = $recorder->record($a, (float) $r->amount, $r->payment_method, $r->paid_at);

        return redirect()->route('payments.show', $p)->with('success', 'Payment recorded.');
    }

    public function show(Payment $payment)
    {
        $payment->load(['appointment.customer', 'appointment.barber', 'transactions' => fn ($q) => $q->orderBy('paid_at')]);

        return view('payments.show', compact('payment'));
    }

    public function receipt(Payment $payment)
    {
        $payment->load(['appointment.customer', 'appointment.barber', 'transactions']);

        return view('payments.receipt',compact('payment'));
    }
}
