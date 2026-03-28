<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Faculty;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DepartmentService
{
    public function create(array $attributes): Department
    {
        return DB::transaction(function () use ($attributes) {
            return Department::create($this->normalize($attributes));
        });
    }

    public function update(Department $department, array $attributes): Department
    {
        return DB::transaction(function () use ($department, $attributes) {
            $normalized = $this->normalize($attributes);

            if (
                $this->hasRecordedPayments($department)
                && (
                    $department->name !== $normalized['name']
                    || (int) $department->faculty_id !== (int) $normalized['faculty_id']
                )
            ) {
                throw ValidationException::withMessages([
                    'name' => 'This department has already been used in payment records and cannot be renamed or moved to another faculty.',
                ]);
            }

            $department->fill($normalized);
            $department->save();

            return $department->refresh();
        });
    }

    public function updateStatus(Department $department, bool $isActive): Department
    {
        $department->update([
            'is_active' => $isActive,
        ]);

        return $department->refresh();
    }

    public function delete(Department $department): void
    {
        if ($this->hasRecordedPayments($department)) {
            throw new DomainException('This department cannot be deleted because it has already been used in payment records.');
        }

        DB::transaction(function () use ($department) {
            $department->delete();
        });
    }

    /**
     * @param  list<int>  $departmentIds
     * @return list<int>
     */
    public function usedDepartmentIds(array $departmentIds): array
    {
        if ($departmentIds === []) {
            return [];
        }

        $departments = Department::query()
            ->with('faculty:id,name')
            ->whereKey($departmentIds)
            ->get();

        if ($departments->isEmpty()) {
            return [];
        }

        $pairs = DB::table('payment_requests')
            ->where(function ($query) use ($departments): void {
                foreach ($departments as $department) {
                    $query->orWhere(function ($nestedQuery) use ($department): void {
                        $nestedQuery
                            ->where('department', $department->name)
                            ->where('faculty', $department->faculty?->name);
                    });
                }
            })
            ->select('department', 'faculty')
            ->distinct()
            ->get()
            ->map(fn (object $record): string => $this->usageKey(
                (string) $record->department,
                (string) $record->faculty,
            ))
            ->all();

        return $departments
            ->filter(fn (Department $department): bool => in_array(
                $this->usageKey($department->name, (string) $department->faculty?->name),
                $pairs,
                true,
            ))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    public function hasRecordedPayments(Department $department): bool
    {
        return DB::table('payment_requests')
            ->where('department', $department->name)
            ->where('faculty', $department->faculty?->name)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function normalize(array $attributes): array
    {
        $name = trim((string) $attributes['name']);
        $displayOrder = $attributes['display_order'] ?? null;

        return [
            'faculty_id' => (int) $attributes['faculty_id'],
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => (bool) ($attributes['is_active'] ?? true),
            'display_order' => $displayOrder === null || $displayOrder === ''
                ? null
                : (int) $displayOrder,
        ];
    }

    protected function usageKey(string $departmentName, string $facultyName): string
    {
        return $facultyName.'|'.$departmentName;
    }
}
