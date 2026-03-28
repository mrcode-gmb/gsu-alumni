<?php

namespace App\Http\Requests\StudentPayments;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\PaymentType;
use App\Models\ProgramType;
use App\Support\GraduationSessionOptions;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => $this->normalizeText($this->input('full_name')),
            'matric_number' => strtoupper($this->normalizeText($this->input('matric_number'))),
            'email' => strtolower(trim((string) $this->input('email', ''))),
            'phone_number' => $this->normalizeText($this->input('phone_number')),
            'department' => $this->normalizeText($this->input('department')),
            'faculty' => $this->normalizeText($this->input('faculty')),
            'program_type_id' => $this->filled('program_type_id') ? trim((string) $this->input('program_type_id')) : null,
            'graduation_session' => $this->normalizeText($this->input('graduation_session')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'matric_number' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'department' => [
                'required',
                'string',
                'max:255',
                Rule::exists(Department::class, 'name')->where(
                    fn (Builder $query) => $query
                        ->where('is_active', true)
                        ->whereIn(
                            'faculty_id',
                            Faculty::query()
                                ->select('id')
                                ->where('name', $this->input('faculty'))
                                ->where('is_active', true),
                        ),
                ),
            ],
            'faculty' => [
                'required',
                'string',
                'max:255',
                Rule::exists(Faculty::class, 'name')->where(
                    fn (Builder $query) => $query->where('is_active', true),
                ),
            ],
            'graduation_session' => ['required', 'string', Rule::in(GraduationSessionOptions::values())],
            'program_type_id' => [
                'required',
                'integer',
                Rule::exists(ProgramType::class, 'id')->where(
                    fn (Builder $query) => $query->where('is_active', true),
                ),
            ],
            'payment_type_id' => [
                'required',
                'integer',
                Rule::exists(PaymentType::class, 'id')->where(
                    fn (Builder $query) => $query
                        ->where('is_active', true)
                        ->whereExists(function (Builder $subQuery): void {
                            $subQuery
                                ->selectRaw('1')
                                ->from('payment_type_program_type')
                                ->whereColumn('payment_type_program_type.payment_type_id', 'payment_types.id')
                                ->where('payment_type_program_type.program_type_id', (int) $this->input('program_type_id'));
                        }),
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'department.exists' => 'Please select a valid department for the selected faculty.',
            'faculty.exists' => 'Please select a valid faculty from the list.',
            'graduation_session.in' => 'Please select a valid graduation session from the list.',
            'program_type_id.exists' => 'Please select a valid program type from the list.',
            'payment_type_id.exists' => 'The selected payment type is invalid or not available for the selected program type.',
            'phone_number.regex' => 'The phone number format is invalid.',
        ];
    }

    protected function normalizeText(mixed $value): string
    {
        return preg_replace('/\s+/', ' ', trim((string) $value)) ?? '';
    }
}
