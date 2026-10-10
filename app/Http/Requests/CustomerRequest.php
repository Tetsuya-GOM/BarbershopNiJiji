<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return ['first_name' => 'required|string|max:100', 'last_name' => 'required|string|max:100', 'contact_number' => ['required', 'regex:/^(\+63|0)9\d{9}$/'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers')->ignore($this->customer)], 'address' => 'nullable|string|max:1000', 'is_active' => 'sometimes|boolean'];
    }
}
