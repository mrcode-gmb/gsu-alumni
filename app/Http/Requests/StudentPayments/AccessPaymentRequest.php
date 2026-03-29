<?php

namespace App\Http\Requests\StudentPayments;

use Illuminate\Foundation\Http\FormRequest;

class AccessPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'matric_number' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'matric_number.required' => 'Matric number is required to access this request.',
            'email.required' => 'Email address is required to access this request.',
        ];
    }
}
