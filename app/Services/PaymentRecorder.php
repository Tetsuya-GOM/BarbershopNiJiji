<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentRecorder
{
    public function record(Appointment $appointment, float $amount, string $method, ?string $paidAt = null): Payment
    {
        return DB::transaction(function () use ($appointment, $amount, $method, $paidAt) {
            $payment = Payment::firstOrCreate(['appointment_id' => $appointment->id], ['total_amount' => $appointment->service_price_snapshot,
                'amount_paid' => 0, 'balance' => $appointment->service_price_snapshot, 'payment_status' => 'unpaid']);
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            if ($amount <= 0 || $amount > (float) $payment->balance) {
                throw ValidationException::withMessages(['amount' => 'Payment must be positive and cannot exceed the remaining balance.']);
            }
            $payment->transactions()->create(['amount' => $amount, 'payment_method' => $method, 'paid_at' => $paidAt ?: now()]);
            $paid = round((float) $payment->amount_paid + $amount, 2);
            $balance = round((float) $payment->total_amount - $paid, 2);
            $payment->update(['amount_paid' => $paid, 'balance' => $balance, 'payment_status' => $balance <= 0 ? 'paid' : 'partially_paid']);

            return $payment->fresh(['transactions', 'appointment.customer']);
        }, 3);
    }
}
