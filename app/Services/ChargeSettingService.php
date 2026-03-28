<?php

namespace App\Services;

use App\Enums\ChargeCalculationMode;
use App\Models\ChargeSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChargeSettingService
{
    public function current(): ChargeSetting
    {
        return ChargeSetting::query()->orderBy('id')->first()
            ?? ChargeSetting::create($this->defaults());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(array $attributes, User $user): ChargeSetting
    {
        return DB::transaction(function () use ($attributes, $user): ChargeSetting {
            $chargeSetting = $this->current();

            $chargeSetting->fill([
                ...$this->normalize($attributes),
                'updated_by' => $user->getKey(),
            ]);
            $chargeSetting->save();

            return $chargeSetting->refresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'portal_charge_mode' => ChargeCalculationMode::Fixed,
            'portal_charge_value' => '0.00',
            'paystack_percentage_rate' => '0.0000',
            'paystack_flat_fee' => '0.00',
            'paystack_flat_fee_threshold' => '0.00',
            'paystack_charge_cap' => '0.00',
            'updated_by' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function normalize(array $attributes): array
    {
        return [
            'portal_charge_mode' => ChargeCalculationMode::from((string) $attributes['portal_charge_mode']),
            'portal_charge_value' => number_format((float) $attributes['portal_charge_value'], 2, '.', ''),
            'paystack_percentage_rate' => number_format((float) $attributes['paystack_percentage_rate'], 4, '.', ''),
            'paystack_flat_fee' => number_format((float) $attributes['paystack_flat_fee'], 2, '.', ''),
            'paystack_flat_fee_threshold' => number_format((float) $attributes['paystack_flat_fee_threshold'], 2, '.', ''),
            'paystack_charge_cap' => number_format((float) $attributes['paystack_charge_cap'], 2, '.', ''),
        ];
    }
}
