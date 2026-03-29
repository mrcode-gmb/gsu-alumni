<?php

namespace App\Http\Requests\Cashier;

use Illuminate\Foundation\Http\FormRequest;

class VerifyReceiptRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'matric_number' => ['required', 'string', 'max:50'],
        ];
    }
}
