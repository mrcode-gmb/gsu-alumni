<?php

namespace App\Http\Requests\Admin\Departments;

use App\Models\Department;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends DepartmentRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Department $department */
        $department = $this->route('department');

        return [
            ...$this->baseRules(),
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Department::class, 'name')
                    ->where(fn (Builder $query) => $query->where('faculty_id', $this->input('faculty_id')))
                    ->ignore($department),
            ],
        ];
    }
}
