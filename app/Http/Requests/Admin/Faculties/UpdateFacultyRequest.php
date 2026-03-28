<?php

namespace App\Http\Requests\Admin\Faculties;

use App\Models\Faculty;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateFacultyRequest extends FacultyRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Faculty $faculty */
        $faculty = $this->route('faculty');

        return [
            ...$this->baseRules(),
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Faculty::class, 'name')->ignore($faculty),
            ],
        ];
    }
}
