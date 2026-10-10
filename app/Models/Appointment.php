<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];

    protected $fillable = ['customer_id', 'barber_id', 'service_id', 'appointment_date', 'start_time', 'end_time', 'status', 'service_name_snapshot', 'service_price_snapshot', 'service_duration_snapshot', 'notes'];

    protected function casts(): array
    {
        return ['appointment_date' => 'date', 'service_price_snapshot' => 'decimal:2'];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function barber()
    {
        return $this->belongsTo(Barber::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function scopeVisibleTo($query, User $user)
    {
        return $user->isAdmin() ? $query : $query->where('barber_id', $user->barber?->id ?? 0);
    }
}
