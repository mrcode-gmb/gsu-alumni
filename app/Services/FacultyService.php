<?php

namespace App\Services;

use App\Models\Faculty;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FacultyService
{
    public function create(array $attributes): Faculty
    {
        return DB::transaction(function () use ($attributes) {
            return Faculty::create($this->normalize($attributes));
        });
    }

    public function update(Faculty $faculty, array $attributes): Faculty
    {
        return DB::transaction(function () use ($faculty, $attributes) {
            $normalized = $this->normalize($attributes);

            if (
                $faculty->name !== $normalized['name']
                && $this->hasRecordedPayments($faculty)
            ) {
                throw ValidationException::withMessages([
                    'name' => 'This faculty has already been used in payment records and cannot be renamed.',
                ]);
            }

            $faculty->fill($normalized);
            $faculty->save();

            return $faculty->refresh();
        });
    }

    public function updateStatus(Faculty $faculty, bool $isActive): Faculty
    {
        $faculty->update([
            'is_active' => $isActive,
        ]);

        return $faculty->refresh();
    }

    public function delete(Faculty $faculty): void
    {
        if ($faculty->departments()->exists()) {
            throw new DomainException('This faculty cannot be deleted because it still has departments assigned to it.');
        }

        if ($this->hasRecordedPayments($faculty)) {
            throw new DomainException('This faculty cannot be deleted because it has already been used in payment records.');
        }

        DB::transaction(function () use ($faculty) {
            $faculty->delete();
        });
    }

    /**
     * @param  list<int>  $facultyIds
     * @return list<int>
     */
    public function usedFacultyIds(array $facultyIds): array
    {
        if ($facultyIds === []) {
            return [];
        }

        $faculties = Faculty::query()
            ->whereKey($facultyIds)
            ->get(['id', 'name']);

        if ($faculties->isEmpty()) {
            return [];
        }

        $usedFacultyNames = DB::table('payment_requests')
            ->whereIn('faculty', $faculties->pluck('name'))
            ->distinct()
            ->pluck('faculty')
            ->all();

        return $faculties
            ->filter(fn (Faculty $faculty): bool => in_array($faculty->name, $usedFacultyNames, true))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    public function hasRecordedPayments(Faculty $faculty): bool
    {
        return DB::table('payment_requests')
            ->where('faculty', $faculty->name)
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
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => (bool) ($attributes['is_active'] ?? true),
            'display_order' => $displayOrder === null || $displayOrder === ''
                ? null
                : (int) $displayOrder,
        ];
    }
}
