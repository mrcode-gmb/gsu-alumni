<?php

namespace App\Http\Requests\Admin\PaymentTypes;

use App\Models\PaymentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdatePaymentTypeRequest extends PaymentTypeRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var PaymentType $paymentType */
        $paymentType = $this->route('paymentType');

        return [
            ...$this->baseRules(),
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(PaymentType::class, 'name')->ignore($paymentType),
            ],
        ];
    }
}
