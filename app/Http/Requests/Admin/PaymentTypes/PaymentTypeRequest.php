<?php

namespace App\Http\Requests\Admin\PaymentTypes;

use App\Models\ProgramType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class PaymentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name', '')),
            'amount' => $this->filled('amount') ? trim((string) $this->input('amount')) : null,
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
            'program_type_ids' => collect($this->input('program_type_ids', []))
                ->map(fn (mixed $programTypeId): ?string => is_scalar($programTypeId) ? trim((string) $programTypeId) : null)
                ->filter(fn (?string $programTypeId): bool => $programTypeId !== null && $programTypeId !== '')
                ->unique()
                ->values()
                ->all(),
            'display_order' => $this->filled('display_order') ? trim((string) $this->input('display_order')) : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function baseRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'description' => ['nullable', 'string', 'max:1000'],
            'program_type_ids' => ['required', 'array', 'min:1'],
            'program_type_ids.*' => ['integer', Rule::exists(ProgramType::class, 'id')],
            'is_active' => ['required', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
