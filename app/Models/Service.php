<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'price', 'duration_minutes', 'status'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }
}
