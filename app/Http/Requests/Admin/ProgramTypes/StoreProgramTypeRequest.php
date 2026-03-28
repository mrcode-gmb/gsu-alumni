<?php

namespace App\Http\Requests\Admin\ProgramTypes;

use App\Models\ProgramType;
use Illuminate\Validation\Rule;

class StoreProgramTypeRequest extends ProgramTypeRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'name' => [
                ...$this->baseRules()['name'],
                Rule::unique(ProgramType::class, 'name'),
            ],
        ];
    }
}
