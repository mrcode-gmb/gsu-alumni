<?php

namespace App\Http\Requests\Admin\PaymentRecords;

use App\Enums\PaymentRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterPaymentRecordRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:150'],
            'payment_type_id' => ['nullable', 'integer', 'exists:payment_types,id'],
            'payment_status' => ['nullable', 'string', Rule::in(array_map(
                static fn (PaymentRequestStatus $status): string => $status->value,
                PaymentRequestStatus::cases(),
            ))],
            'department' => ['nullable', 'string', 'max:120'],
            'faculty' => ['nullable', 'string', 'max:120'],
            'graduation_session' => ['nullable', 'string', 'max:50'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'string', Rule::in([
                'newest',
                'oldest',
                'amount_asc',
                'amount_desc',
                'name_asc',
                'name_desc',
            ])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->trimmed('search'),
            'payment_type_id' => $this->emptyToNull('payment_type_id'),
            'payment_status' => $this->emptyToNull('payment_status'),
            'department' => $this->trimmed('department'),
            'faculty' => $this->trimmed('faculty'),
            'graduation_session' => $this->trimmed('graduation_session'),
            'date_from' => $this->emptyToNull('date_from'),
            'date_to' => $this->emptyToNull('date_to'),
            'sort' => $this->emptyToNull('sort'),
        ]);
    }

    protected function trimmed(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : preg_replace('/\s+/', ' ', $value);
    }

    protected function emptyToNull(string $key): mixed
    {
        $value = $this->input($key);

        return $value === '' ? null : $value;
    }
}
