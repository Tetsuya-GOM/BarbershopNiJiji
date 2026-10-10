<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return ['appointment_id' => 'required|exists:appointments,id', 'amount' => 'required|numeric|min:0.01', 'payment_method' => ['required', Rule::in(['cash', 'gcash'])], 'paid_at' => 'nullable|date'];
    }
}
