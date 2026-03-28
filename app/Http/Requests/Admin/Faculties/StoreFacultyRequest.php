<?php

namespace App\Http\Requests\Admin\Faculties;

use App\Models\Faculty;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreFacultyRequest extends FacultyRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Faculty::class, 'name'),
            ],
        ];
    }
}
