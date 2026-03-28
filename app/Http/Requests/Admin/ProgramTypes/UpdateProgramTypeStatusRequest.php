<?php

namespace App\Http\Requests\Admin\ProgramTypes;

class UpdateProgramTypeStatusRequest extends ProgramTypeRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }
}
