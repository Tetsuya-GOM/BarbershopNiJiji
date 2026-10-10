<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return ['customer_id' => 'required|exists:customers,id', 'barber_id' => 'required|exists:barbers,id', 'service_id' => 'required|exists:services,id',
            'appointment_date' => 'required|date|after_or_equal:today', 'start_time' => 'required|date_format:H:i', 'status' => ['required', Rule::in(Appointment::STATUSES)], 'notes' => 'nullable|string|max:2000'];
    }
}
