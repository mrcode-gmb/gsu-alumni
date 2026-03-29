<?php

namespace App\Services;

use App\Models\PaymentType;

class PaymentTypeChargeService
{
    public function __construct(
        protected PaymentChargeCalculator $paymentChargeCalculator,
    ) {
    }

    /**
     * @return array{
     *     base_amount: string,
     *     service_charge_amount: string,
     *     paystack_charge_amount: string,
     *     total_amount: string,
     *     snapshot: array<string, mixed>
     * }
     */
    public function calculateForBaseAmount(string $baseAmount): array
    {
        $calculated = $this->paymentChargeCalculator->calculateForBaseAmount($baseAmount);

        return [
            'base_amount' => $calculated['base_amount'],
            'service_charge_amount' => $calculated['portal_charge_amount'],
            'paystack_charge_amount' => $calculated['paystack_charge_amount'],
            'total_amount' => $calculated['total_amount'],
            'snapshot' => $calculated['charge_settings_snapshot'],
        ];
    }

    /**
     * @return array{
     *     base_amount: string,
     *     service_charge_amount: string,
     *     paystack_charge_amount: string,
     *     total_amount: string,
     *     snapshot: array<string, mixed>
     * }
     */
    public function resolveForPaymentType(PaymentType $paymentType): array
    {
        if (
            $paymentType->service_charge_amount !== null
            && $paymentType->paystack_charge_amount !== null
        ) {
            $baseAmount = number_format((float) $paymentType->amount, 2, '.', '');
            $serviceChargeAmount = number_format((float) $paymentType->service_charge_amount, 2, '.', '');
            $paystackChargeAmount = number_format((float) $paymentType->paystack_charge_amount, 2, '.', '');

            return [
                'base_amount' => $baseAmount,
                'service_charge_amount' => $serviceChargeAmount,
                'paystack_charge_amount' => $paystackChargeAmount,
                'total_amount' => number_format(
                    (float) $baseAmount + (float) $serviceChargeAmount + (float) $paystackChargeAmount,
                    2,
                    '.',
                    '',
                ),
                'snapshot' => [
                    'source' => 'payment_type',
                    'payment_type_id' => $paymentType->getKey(),
                    'service_charge_amount' => $serviceChargeAmount,
                    'paystack_charge_amount' => $paystackChargeAmount,
                    'resolved_at' => now()->toIso8601String(),
                ],
            ];
        }

        return $this->calculateForBaseAmount((string) $paymentType->amount);
    }
}
