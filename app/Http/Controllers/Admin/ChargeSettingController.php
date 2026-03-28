<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ChargeCalculationMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChargeSettings\UpdateChargeSettingRequest;
use App\Models\ChargeSetting;
use App\Services\ChargeSettingService;
use App\Services\PaymentChargeCalculator;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ChargeSettingController extends Controller
{
    public function __construct(
        protected ChargeSettingService $chargeSettingService,
        protected PaymentChargeCalculator $paymentChargeCalculator,
    ) {
    }

    public function edit(): Response
    {
        $chargeSetting = $this->chargeSettingService->current();

        return Inertia::render('admin/charge-settings/edit', [
            'chargeSetting' => $this->chargeSettingPayload($chargeSetting),
            'modeOptions' => collect(ChargeCalculationMode::cases())
                ->map(fn (ChargeCalculationMode $mode): array => [
                    'value' => $mode->value,
                    'label' => $mode->label(),
                ])
                ->values(),
            'previewSamples' => collect(['2500.00', '5000.00', '10000.00', '25000.00'])
                ->map(fn (string $baseAmount): array => [
                    'base_amount' => $baseAmount,
                    ...$this->paymentChargeCalculator->calculateForBaseAmount($baseAmount, $chargeSetting),
                ])
                ->values(),
        ]);
    }

    public function update(UpdateChargeSettingRequest $request): RedirectResponse
    {
        $this->chargeSettingService->update($request->validated(), $request->user());

        return back()->with('success', 'Charge settings updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function chargeSettingPayload(ChargeSetting $chargeSetting): array
    {
        return [
            'portal_charge_mode' => $chargeSetting->portal_charge_mode->value,
            'portal_charge_value' => (string) $chargeSetting->portal_charge_value,
            'paystack_percentage_rate' => number_format((float) $chargeSetting->paystack_percentage_rate, 4, '.', ''),
            'paystack_flat_fee' => (string) $chargeSetting->paystack_flat_fee,
            'paystack_flat_fee_threshold' => (string) $chargeSetting->paystack_flat_fee_threshold,
            'paystack_charge_cap' => (string) $chargeSetting->paystack_charge_cap,
            'updated_at' => $chargeSetting->updated_at?->toIso8601String(),
            'updated_by_name' => $chargeSetting->updatedBy?->name,
        ];
    }
}
