<?php

namespace App\Http\Requests\Admin\ChargeSettings;

use App\Enums\ChargeCalculationMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChargeSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'portal_charge_mode' => trim((string) $this->input('portal_charge_mode', ChargeCalculationMode::Fixed->value)),
            'portal_charge_value' => $this->filled('portal_charge_value') ? trim((string) $this->input('portal_charge_value')) : '0',
            'paystack_percentage_rate' => $this->filled('paystack_percentage_rate') ? trim((string) $this->input('paystack_percentage_rate')) : '0',
            'paystack_flat_fee' => $this->filled('paystack_flat_fee') ? trim((string) $this->input('paystack_flat_fee')) : '0',
            'paystack_flat_fee_threshold' => $this->filled('paystack_flat_fee_threshold') ? trim((string) $this->input('paystack_flat_fee_threshold')) : '0',
            'paystack_charge_cap' => $this->filled('paystack_charge_cap') ? trim((string) $this->input('paystack_charge_cap')) : '0',
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'portal_charge_mode' => ['required', 'string', Rule::in(array_column(ChargeCalculationMode::cases(), 'value'))],
            'portal_charge_value' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999.99'],
            'paystack_percentage_rate' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,4'],
            'paystack_flat_fee' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999.99'],
            'paystack_flat_fee_threshold' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999.99'],
            'paystack_charge_cap' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'portal_charge_value.min' => 'Your own charge cannot be negative.',
            'paystack_percentage_rate.max' => 'Paystack percentage rate cannot be greater than 100.',
        ];
    }
}
