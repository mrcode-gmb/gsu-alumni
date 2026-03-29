<?php

namespace App\Services;

use App\Enums\PaymentRequestStatus;
use App\Models\PaymentRequest;
use App\Models\PaymentType;
use App\Models\ProgramType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentRequestService
{
    public function __construct(
        protected PaymentTypeChargeService $paymentTypeChargeService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{paymentRequest: PaymentRequest, reused: bool}
     */
    public function createOrReusePending(array $attributes): array
    {
        return DB::transaction(function () use ($attributes): array {
            $programType = ProgramType::query()
                ->whereKey($attributes['program_type_id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $programType) {
                throw ValidationException::withMessages([
                    'program_type_id' => 'The selected program type is invalid or no longer available.',
                ]);
            }

            $paymentType = PaymentType::query()
                ->whereHas('programTypes', fn ($query) => $query->whereKey($programType->getKey()))
                ->whereKey($attributes['payment_type_id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $paymentType) {
                throw ValidationException::withMessages([
                    'payment_type_id' => 'The selected payment type is invalid or not available for the selected program type.',
                ]);
            }

            $normalizedAttributes = $this->normalize($attributes);
            $payload = $this->buildPayload($normalizedAttributes, $programType, $paymentType);

            $paymentRequest = PaymentRequest::query()
                ->where('matric_number', $payload['matric_number'])
                ->where('payment_type_id', $paymentType->getKey())
                ->where('payment_status', PaymentRequestStatus::Pending)
                ->lockForUpdate()
                ->first();

            if ($paymentRequest) {
                // Keep a single open request per student and payment type until the payment is completed.
                $paymentRequest->fill($payload);
                $paymentRequest->save();

                return [
                    'paymentRequest' => $paymentRequest->refresh(),
                    'reused' => true,
                ];
            }

            $paymentRequest = PaymentRequest::create([
                ...$payload,
                'public_reference' => (string) Str::ulid(),
            ]);

            return [
                'paymentRequest' => $paymentRequest,
                'reused' => false,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function normalize(array $attributes): array
    {
        return [
            'full_name' => $this->normalizeText($attributes['full_name'] ?? ''),
            'matric_number' => strtoupper($this->normalizeText($attributes['matric_number'] ?? '')),
            'email' => strtolower(trim((string) ($attributes['email'] ?? ''))),
            'phone_number' => $this->normalizeText($attributes['phone_number'] ?? ''),
            'department' => $this->normalizeText($attributes['department'] ?? ''),
            'faculty' => $this->normalizeText($attributes['faculty'] ?? ''),
            'program_type_id' => isset($attributes['program_type_id']) ? (int) $attributes['program_type_id'] : null,
            'graduation_session' => $this->normalizeText($attributes['graduation_session'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function buildPayload(array $attributes, ProgramType $programType, PaymentType $paymentType): array
    {
        $chargeBreakdown = $this->paymentTypeChargeService->resolveForPaymentType($paymentType);

        return [
            ...$attributes,
            'program_type_id' => $programType->getKey(),
            'program_type_name' => $programType->name,
            'payment_type_id' => $paymentType->getKey(),
            'payment_type_name' => $paymentType->name,
            'payment_type_description' => $paymentType->description,
            'base_amount' => $chargeBreakdown['base_amount'],
            'portal_charge_amount' => $chargeBreakdown['service_charge_amount'],
            'paystack_charge_amount' => $chargeBreakdown['paystack_charge_amount'],
            'charge_settings_snapshot' => $chargeBreakdown['snapshot'],
            'amount' => $chargeBreakdown['total_amount'],
            'payment_status' => PaymentRequestStatus::Pending,
            'payment_reference' => null,
            'paystack_reference' => null,
            'paid_at' => null,
            'payment_channel' => null,
            'gateway_response' => null,
            'initialization_payload' => null,
            'verification_payload' => null,
            'transaction_reference' => null,
        ];
    }

    protected function normalizeText(mixed $value): string
    {
        return preg_replace('/\s+/', ' ', trim((string) $value)) ?? '';
    }
}
