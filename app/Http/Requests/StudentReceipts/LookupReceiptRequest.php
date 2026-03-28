<?php

namespace App\Http\Requests\StudentReceipts;

use Illuminate\Foundation\Http\FormRequest;

class LookupReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'receipt_number' => ['required', 'string', 'max:80'],
            'matric_number' => ['required', 'string', 'max:80'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $receiptNumber = trim((string) $this->input('receipt_number'));
        $matricNumber = trim((string) $this->input('matric_number'));

        $this->merge([
            'receipt_number' => strtoupper($receiptNumber),
            'matric_number' => strtoupper(preg_replace('/\s+/', ' ', $matricNumber) ?? ''),
        ]);
    }
}
