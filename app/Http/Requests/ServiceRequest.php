<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:150', Rule::unique('services')->ignore($this->service)], 'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0|max:99999999', 'duration_minutes' => 'required|integer|min:5|max:480', 'status' => ['required', Rule::in(['available', 'unavailable'])]];
    }
}
