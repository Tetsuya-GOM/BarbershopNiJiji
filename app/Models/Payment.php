<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['appointment_id', 'total_amount', 'amount_paid', 'balance', 'payment_status'];

    protected function casts(): array
    {
        return ['total_amount' => 'decimal:2', 'amount_paid' => 'decimal:2', 'balance' => 'decimal:2'];
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
