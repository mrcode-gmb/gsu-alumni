<?php

namespace App\Services;

use App\Models\PaymentType;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentTypeService
{
    public function create(array $attributes): PaymentType
    {
        return DB::transaction(function () use ($attributes) {
            $paymentType = PaymentType::create($this->normalize($attributes));
            $paymentType->programTypes()->sync($this->programTypeIds($attributes));

            return $paymentType->load('programTypes');
        });
    }

    public function update(PaymentType $paymentType, array $attributes): PaymentType
    {
        return DB::transaction(function () use ($paymentType, $attributes) {
            $paymentType->fill($this->normalize($attributes));
            $paymentType->save();
            $paymentType->programTypes()->sync($this->programTypeIds($attributes));

            return $paymentType->refresh()->load('programTypes');
        });
    }

    public function updateStatus(PaymentType $paymentType, bool $isActive): PaymentType
    {
        $paymentType->update([
            'is_active' => $isActive,
        ]);

        return $paymentType->refresh();
    }

    public function delete(PaymentType $paymentType): void
    {
        if ($this->hasRecordedPayments($paymentType)) {
            throw new DomainException('This payment type cannot be deleted because it has already been used in payment records.');
        }

        DB::transaction(function () use ($paymentType) {
            $paymentType->delete();
        });
    }

    /**
     * @param  list<int>  $paymentTypeIds
     * @return list<int>
     */
    public function usedPaymentTypeIds(array $paymentTypeIds): array
    {
        if ($paymentTypeIds === []) {
            return [];
        }

        $usedPaymentTypeIds = [];

        foreach (['payment_requests', 'payments'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $usedPaymentTypeIds = [
                ...$usedPaymentTypeIds,
                ...DB::table($table)
                    ->whereIn('payment_type_id', $paymentTypeIds)
                    ->distinct()
                    ->pluck('payment_type_id')
                    ->map(fn (mixed $paymentTypeId): int => (int) $paymentTypeId)
                    ->all(),
            ];
        }

        return array_values(array_unique($usedPaymentTypeIds));
    }

    public function hasRecordedPayments(PaymentType $paymentType): bool
    {
        foreach (['payment_requests', 'payments'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (DB::table($table)
                ->where('payment_type_id', $paymentType->getKey())
                ->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function normalize(array $attributes): array
    {
        $displayOrder = $attributes['display_order'] ?? null;

        return [
            'name' => trim((string) $attributes['name']),
            'amount' => number_format((float) $attributes['amount'], 2, '.', ''),
            'description' => filled($attributes['description'] ?? null)
                ? trim((string) $attributes['description'])
                : null,
            'is_active' => (bool) ($attributes['is_active'] ?? true),
            'display_order' => $displayOrder === null || $displayOrder === ''
                ? null
                : (int) $displayOrder,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return list<int>
     */
    protected function programTypeIds(array $attributes): array
    {
        return collect($attributes['program_type_ids'] ?? [])
            ->map(fn (mixed $programTypeId): int => (int) $programTypeId)
            ->filter(fn (int $programTypeId): bool => $programTypeId > 0)
            ->unique()
            ->values()
            ->all();
    }
}
