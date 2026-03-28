<?php

namespace App\Services;

use App\Models\ProgramType;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProgramTypeService
{
    public function create(array $attributes): ProgramType
    {
        return DB::transaction(function () use ($attributes) {
            return ProgramType::create($this->normalize($attributes));
        });
    }

    public function update(ProgramType $programType, array $attributes): ProgramType
    {
        return DB::transaction(function () use ($programType, $attributes) {
            $programType->fill($this->normalize($attributes));
            $programType->save();

            return $programType->refresh();
        });
    }

    public function updateStatus(ProgramType $programType, bool $isActive): ProgramType
    {
        $programType->update([
            'is_active' => $isActive,
        ]);

        return $programType->refresh();
    }

    public function delete(ProgramType $programType): void
    {
        if ($this->hasRecordedPayments($programType)) {
            throw new DomainException('This program type cannot be deleted because it is already linked to payment records or payment types.');
        }

        DB::transaction(function () use ($programType) {
            $programType->delete();
        });
    }

    /**
     * @param  list<int>  $programTypeIds
     * @return list<int>
     */
    public function usedProgramTypeIds(array $programTypeIds): array
    {
        if ($programTypeIds === []) {
            return [];
        }

        $usedInRequests = DB::table('payment_requests')
            ->whereIn('program_type_id', $programTypeIds)
            ->distinct()
            ->pluck('program_type_id')
            ->map(fn (mixed $programTypeId): int => (int) $programTypeId)
            ->all();

        $usedInPaymentTypes = DB::table('payment_type_program_type')
            ->whereIn('program_type_id', $programTypeIds)
            ->distinct()
            ->pluck('program_type_id')
            ->map(fn (mixed $programTypeId): int => (int) $programTypeId)
            ->all();

        return array_values(array_unique([
            ...$usedInRequests,
            ...$usedInPaymentTypes,
        ]));
    }

    public function hasRecordedPayments(ProgramType $programType): bool
    {
        return DB::table('payment_requests')
            ->where('program_type_id', $programType->getKey())
            ->exists()
            || DB::table('payment_type_program_type')
                ->where('program_type_id', $programType->getKey())
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
            'description' => filled($attributes['description'] ?? null)
                ? trim((string) $attributes['description'])
                : null,
            'is_active' => (bool) ($attributes['is_active'] ?? true),
            'display_order' => $displayOrder === null || $displayOrder === ''
                ? null
                : (int) $displayOrder,
        ];
    }
}
