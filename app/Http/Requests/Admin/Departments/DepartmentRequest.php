<?php

namespace App\Http\Requests\Admin\Departments;

use App\Models\Faculty;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'faculty_id' => $this->filled('faculty_id') ? trim((string) $this->input('faculty_id')) : null,
            'name' => trim((string) $this->input('name', '')),
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
            'faculty_id' => ['required', 'integer', Rule::exists(Faculty::class, 'id')],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
