<?php

namespace App\Http\Requests\Admin\ProgramTypes;

use App\Models\ProgramType;
use Illuminate\Validation\Rule;

class UpdateProgramTypeRequest extends ProgramTypeRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var ProgramType $programType */
        $programType = $this->route('programType');

        return [
            ...$this->baseRules(),
            'name' => [
                ...$this->baseRules()['name'],
                Rule::unique(ProgramType::class, 'name')->ignore($programType),
            ],
        ];
    }
}
