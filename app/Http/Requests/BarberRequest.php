<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BarberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return ['first_name' => 'required|string|max:100', 'last_name' => 'required|string|max:100', 'contact_number' => ['required', 'regex:/^(\+63|0)9\d{9}$/'],
            'email' => ['required', 'email', 'max:255', Rule::unique('barbers')->ignore($this->barber)], 'specialization' => 'nullable|string|max:255',
            'status' => ['required', Rule::in(['active', 'inactive'])], 'profile_photo' => 'nullable|image|max:2048', 'create_login' => 'sometimes|boolean', 'password' => 'nullable|string|min:8'];
    }
}
