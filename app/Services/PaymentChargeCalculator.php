<?php

namespace App\Services;

use App\Enums\ChargeCalculationMode;
use App\Models\ChargeSetting;

class PaymentChargeCalculator
{
    public function __construct(
        protected ChargeSettingService $chargeSettingService,
    ) {
    }

    /**
     * @return array{
     *     base_amount: string,
     *     portal_charge_amount: string,
     *     paystack_charge_amount: string,
     *     total_amount: string,
     *     charge_settings_snapshot: array<string, mixed>
     * }
     */
    public function calculateForBaseAmount(string $baseAmount, ?ChargeSetting $chargeSetting = null): array
    {
        $chargeSetting ??= $this->chargeSettingService->current();

        $baseAmountKobo = $this->toKobo($baseAmount);
        $portalChargeKobo = $this->portalChargeKobo($baseAmountKobo, $chargeSetting);
        $subtotalKobo = $baseAmountKobo + $portalChargeKobo;
        $paystackChargeKobo = $this->paystackChargeGrossUpKobo($subtotalKobo, $chargeSetting);
        $totalAmountKobo = $subtotalKobo + $paystackChargeKobo;

        return [
            'base_amount' => $this->fromKobo($baseAmountKobo),
            'portal_charge_amount' => $this->fromKobo($portalChargeKobo),
            'paystack_charge_amount' => $this->fromKobo($paystackChargeKobo),
            'total_amount' => $this->fromKobo($totalAmountKobo),
            'charge_settings_snapshot' => [
                'portal_charge' => [
                    'mode' => $chargeSetting->portal_charge_mode->value,
                    'mode_label' => $chargeSetting->portal_charge_mode->label(),
                    'configured_value' => (string) $chargeSetting->portal_charge_value,
                    'amount' => $this->fromKobo($portalChargeKobo),
                ],
                'paystack_charge' => [
                    'percentage_rate' => number_format((float) $chargeSetting->paystack_percentage_rate, 4, '.', ''),
                    'flat_fee' => (string) $chargeSetting->paystack_flat_fee,
                    'flat_fee_threshold' => (string) $chargeSetting->paystack_flat_fee_threshold,
                    'amount' => $this->fromKobo($paystackChargeKobo),
                ],
                'calculated_at' => now()->toIso8601String(),
            ],
        ];
    }

    protected function portalChargeKobo(int $baseAmountKobo, ChargeSetting $chargeSetting): int
    {
        if ($baseAmountKobo <= 0) {
            return 0;
        }

        return match ($chargeSetting->portal_charge_mode) {
            ChargeCalculationMode::Fixed => $this->toKobo((string) $chargeSetting->portal_charge_value),
            ChargeCalculationMode::Percentage => (int) ceil(
                $baseAmountKobo * ((float) $chargeSetting->portal_charge_value / 100),
            ),
        };
    }

    protected function paystackChargeGrossUpKobo(int $subtotalKobo, ChargeSetting $chargeSetting): int
    {
        if ($subtotalKobo <= 0) {
            return 0;
        }

        $grossAmountKobo = $subtotalKobo;

        for ($attempt = 0; $attempt < 12; $attempt++) {
            $feeKobo = $this->paystackFeeForGrossKobo($grossAmountKobo, $chargeSetting);
            $nextGrossAmountKobo = $subtotalKobo + $feeKobo;

            if ($nextGrossAmountKobo === $grossAmountKobo) {
                break;
            }

            $grossAmountKobo = $nextGrossAmountKobo;
        }

        return max(0, $grossAmountKobo - $subtotalKobo);
    }

    protected function paystackFeeForGrossKobo(int $grossAmountKobo, ChargeSetting $chargeSetting): int
    {
        $percentageRate = max(0, (float) $chargeSetting->paystack_percentage_rate);
        $flatFeeKobo = $this->toKobo((string) $chargeSetting->paystack_flat_fee);
        $thresholdKobo = $this->toKobo((string) $chargeSetting->paystack_flat_fee_threshold);

        $feeKobo = $percentageRate > 0
            ? (int) ceil($grossAmountKobo * ($percentageRate / 100))
            : 0;

        if ($flatFeeKobo > 0 && $grossAmountKobo >= $thresholdKobo) {
            $feeKobo += $flatFeeKobo;
        }

        return max(0, $feeKobo);
    }

    protected function toKobo(string $amount): int
    {
        return (int) str_replace('.', '', number_format((float) $amount, 2, '.', ''));
    }

    protected function fromKobo(int $amountKobo): string
    {
        return number_format($amountKobo / 100, 2, '.', '');
    }
}
