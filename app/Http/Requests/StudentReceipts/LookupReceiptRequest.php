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
            'email' => ['required', 'email:rfc', 'max:255'],
            'matric_number' => ['required', 'string', 'max:80'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $email = trim((string) $this->input('email'));
        $matricNumber = trim((string) $this->input('matric_number'));

        $this->merge([
            'email' => strtolower($email),
            'matric_number' => strtoupper(preg_replace('/\s+/', ' ', $matricNumber) ?? ''),
        ]);
    }
}
