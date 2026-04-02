<?php

namespace App\Services;

use App\Enums\PaymentRequestStatus;
use App\Models\PaymentRequest;
use App\Models\PaymentType;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentCheckoutService
{
    public function __construct(
        protected PaystackService $paystackService,
    ) {
    }

    /**
     * @return array{paymentRequest: PaymentRequest, authorizationUrl: string}
     */
    public function initializePayment(PaymentRequest $paymentRequest): array
    {
        $paymentRequest = $this->preparePaymentRequestForCheckout($paymentRequest);
        $existingAuthorizationUrl = data_get($paymentRequest->initialization_payload, 'data.authorization_url');

        if (is_string($existingAuthorizationUrl) && $existingAuthorizationUrl !== '') {
            return [
                'paymentRequest' => $paymentRequest,
                'authorizationUrl' => $existingAuthorizationUrl,
            ];
        }

        $paymentReference = $paymentRequest->payment_reference ?? $this->generateInternalReference();
        $paystackReference = $paymentRequest->paystack_reference ?? $this->generateGatewayReference($paymentReference);

        $payload = [
            'email' => $paymentRequest->email,
            'amount' => $this->amountInSubunit($paymentRequest->amount),
            'reference' => $paystackReference,
            'currency' => (string) config('services.paystack.currency', 'NGN'),
            'callback_url' => $this->callbackUrl(),
            'metadata' => $this->checkoutMetadata($paymentRequest),
        ];

        $payload = $this->applySplitConfig($payload, $paymentRequest);

        $response = $this->paystackService->initializeTransaction($payload);
        $data = $response['data'] ?? [];
        $authorizationUrl = (string) ($data['authorization_url'] ?? '');
        $accessCode = (string) ($data['access_code'] ?? '');
        $responseReference = (string) ($data['reference'] ?? '');

        if ($authorizationUrl === '' || $accessCode === '' || $responseReference === '') {
            Log::warning('Paystack initialization returned an incomplete response.', [
                'payment_request_id' => $paymentRequest->id,
                'payment_reference' => $paymentReference,
                'paystack_reference' => $paystackReference,
                'response' => $response,
            ]);

            throw new RuntimeException('Paystack did not return a valid initialization response.');
        }

        Log::info('Paystack initialize response stored.', [
            'payment_request_id' => $paymentRequest->id,
            'payment_reference' => $paymentReference,
            'paystack_reference' => $paystackReference,
            'response' => $response,
        ]);

        $paymentRequest->forceFill([
            'payment_reference' => $paymentReference,
            'paystack_reference' => $paystackReference,
            'gateway_response' => (string) ($response['message'] ?? 'Authorization URL created'),
            'transaction_reference' => $accessCode,
            'initialization_payload' => $response,
        ])->save();

        Log::info('Paystack transaction initialized.', [
            'payment_request_id' => $paymentRequest->id,
            'payment_reference' => $paymentReference,
            'paystack_reference' => $paystackReference,
        ]);

        return [
            'paymentRequest' => $paymentRequest->refresh(),
            'authorizationUrl' => $authorizationUrl,
        ];
    }

    /**
     * @return array{
     *     paymentRequest: PaymentRequest,
     *     checkout: array{
     *         key: string,
     *         email: string,
     *         amount: int,
     *         currency: string,
     *         reference: string,
     *         callback_url: string,
     *         metadata: array<string, mixed>
     *     }
     * }
     */
    public function preparePopupPayment(PaymentRequest $paymentRequest): array
    {
        $paymentRequest = $this->initializePayment($paymentRequest)['paymentRequest'];
        $publicKey = (string) config('services.paystack.public_key');

        if ($publicKey === '') {
            throw new RuntimeException('Paystack public key is not configured yet.');
        }

        $paymentReference = $paymentRequest->payment_reference ?? $this->generateInternalReference();
        $paystackReference = $paymentRequest->paystack_reference ?? $this->generateGatewayReference($paymentReference);

        $checkout = [
            'key' => $publicKey,
            'email' => $paymentRequest->email,
            'amount' => $this->amountInSubunit($paymentRequest->amount),
            'currency' => (string) config('services.paystack.currency', 'NGN'),
            'reference' => $paystackReference,
            'callback_url' => $this->callbackUrl(),
            'metadata' => $this->checkoutMetadata($paymentRequest),
        ];

        $checkout = $this->applySplitConfig($checkout, $paymentRequest);

        $existingPayload = is_array($paymentRequest->initialization_payload)
            ? $paymentRequest->initialization_payload
            : [];

        $popupPayload = [
            'mode' => 'popup',
            'prepared_at' => now()->toIso8601String(),
            'checkout' => [
                'email' => $checkout['email'],
                'amount' => $checkout['amount'],
                'currency' => $checkout['currency'],
                'reference' => $checkout['reference'],
                'callback_url' => $checkout['callback_url'],
                'metadata' => $checkout['metadata'],
                'split_code' => $checkout['split_code'] ?? null,
                'subaccount' => $checkout['subaccount'] ?? null,
                'transaction_charge' => $checkout['transaction_charge'] ?? null,
                'bearer' => $checkout['bearer'] ?? null,
            ],
        ];

        $paymentRequest->forceFill([
            'payment_reference' => $paymentReference,
            'paystack_reference' => $paystackReference,
            'gateway_response' => 'Popup checkout prepared.',
            'initialization_payload' => [
                ...$existingPayload,
                'popup' => $popupPayload,
            ],
        ])->save();

        Log::info('Paystack popup checkout prepared.', [
            'payment_request_id' => $paymentRequest->id,
            'payment_reference' => $paymentReference,
            'paystack_reference' => $paystackReference,
            'checkout' => $checkout,
        ]);

        return [
            'paymentRequest' => $paymentRequest->refresh(),
            'checkout' => $checkout,
        ];
    }

    public function markPaymentAsAbandoned(
        PaymentRequest $paymentRequest,
        string $message = 'Payment was cancelled before completion.',
    ): PaymentRequest {
        return DB::transaction(function () use ($paymentRequest, $message): PaymentRequest {
            $lockedPaymentRequest = PaymentRequest::query()
                ->lockForUpdate()
                ->findOrFail($paymentRequest->getKey());

            if ($lockedPaymentRequest->payment_status === PaymentRequestStatus::Pending) {
                $lockedPaymentRequest->forceFill([
                    'payment_status' => PaymentRequestStatus::Abandoned,
                    'gateway_response' => $message,
                    'verification_payload' => [
                        'status' => false,
                        'source' => 'cancel_action',
                        'message' => $message,
                        'cancelled_at' => now()->toIso8601String(),
                    ],
                ])->save();

                Log::info('Pending payment request marked as abandoned after Paystack cancellation.', [
                    'payment_request_id' => $lockedPaymentRequest->id,
                    'paystack_reference' => $lockedPaymentRequest->paystack_reference,
                ]);
            }

            return $lockedPaymentRequest->refresh();
        });
    }

    /**
     * @return array{paymentRequest: PaymentRequest, message: string}
     */
    public function verifyPaymentByReference(string $reference): array
    {
        $reference = trim($reference);

        if ($reference === '') {
            throw new DomainException('The payment reference is missing from the Paystack callback.');
        }

        return DB::transaction(function () use ($reference): array {
            $paymentRequest = PaymentRequest::query()
                ->where('paystack_reference', $reference)
                ->orWhere('payment_reference', $reference)
                ->lockForUpdate()
                ->first();

            if (! $paymentRequest) {
                Log::warning('Paystack callback received for an unknown reference.', [
                    'reference' => $reference,
                ]);

                throw new DomainException('We could not match this Paystack payment to a valid request.');
            }

            if ($paymentRequest->payment_status->isSuccessful()) {
                Log::info('Duplicate Paystack verification ignored because payment is already successful.', [
                    'payment_request_id' => $paymentRequest->id,
                    'reference' => $reference,
                ]);

                return [
                    'paymentRequest' => $paymentRequest,
                    'message' => 'This payment has already been verified successfully.',
                ];
            }

            $response = $this->paystackService->verifyTransaction($reference);
            $data = $response['data'] ?? [];

            Log::info('Paystack verify response received.', [
                'payment_request_id' => $paymentRequest->id,
                'reference' => $reference,
                'response' => $response,
            ]);

            $this->guardVerifiedPayloadIntegrity($paymentRequest, $reference, $data);

            $normalizedStatus = $this->normalizeGatewayStatus((string) ($data['status'] ?? 'pending'));
            $message = $this->applyVerificationResult($paymentRequest, $normalizedStatus, $response, $data);

            return [
                'paymentRequest' => $paymentRequest->refresh(),
                'message' => $message,
            ];
        });
    }

    /**
     * @return array{paymentRequest: PaymentRequest, message: string}
     */
    public function verifyExistingPaymentRequest(PaymentRequest $paymentRequest): array
    {
        if ($paymentRequest->initialization_payload === null) {
            throw new DomainException('This payment request has not been initialized with Paystack yet.');
        }

        $reference = $paymentRequest->paystack_reference ?? $paymentRequest->payment_reference;

        if ($reference === null) {
            throw new DomainException('This payment request has not been initialized with Paystack yet.');
        }

        return $this->verifyPaymentByReference($reference);
    }

    public function handleWebhookEvent(array $payload): ?PaymentRequest
    {
        $event = strtolower(trim((string) ($payload['event'] ?? '')));

        if ($event !== 'charge.success') {
            Log::info('Ignored unsupported Paystack webhook event.', [
                'event' => $payload['event'] ?? null,
            ]);

            return null;
        }

        $reference = trim((string) data_get($payload, 'data.reference', ''));

        if ($reference === '') {
            Log::warning('Paystack charge.success webhook did not include a reference.', [
                'event' => $payload['event'] ?? null,
            ]);

            return null;
        }

        $result = $this->verifyPaymentByReference($reference);

        return $result['paymentRequest'];
    }

    /**
     * @param  array<string, mixed>  $verifiedData
     */
    protected function guardVerifiedPayloadIntegrity(
        PaymentRequest $paymentRequest,
        string $reference,
        array $verifiedData,
    ): void {
        $verifiedReference = (string) ($verifiedData['reference'] ?? '');
        $expectedAmount = $this->amountInSubunit($paymentRequest->amount);
        $verifiedAmount = (int) ($verifiedData['amount'] ?? 0);
        $verifiedCurrency = strtoupper((string) ($verifiedData['currency'] ?? ''));
        $expectedCurrency = strtoupper((string) config('services.paystack.currency', 'NGN'));
        $verifiedEmail = strtolower((string) data_get($verifiedData, 'customer.email', ''));
        $expectedEmail = strtolower($paymentRequest->email);
        $verifiedRequestId = data_get($verifiedData, 'metadata.payment_request_id');
        $verifiedPublicReference = (string) data_get($verifiedData, 'metadata.public_reference', '');
        $verifiedPaymentTypeId = data_get($verifiedData, 'metadata.payment_type_id');
        $metadataMismatch = false;

        if ($verifiedRequestId !== null && (int) $verifiedRequestId !== (int) $paymentRequest->id) {
            $metadataMismatch = true;
        }

        if ($verifiedPublicReference !== '' && $verifiedPublicReference !== $paymentRequest->public_reference) {
            $metadataMismatch = true;
        }

        if ($verifiedPaymentTypeId !== null && (int) $verifiedPaymentTypeId !== (int) $paymentRequest->payment_type_id) {
            $metadataMismatch = true;
        }

        if (
            $verifiedReference !== $reference
            || $verifiedAmount !== $expectedAmount
            || $verifiedCurrency !== $expectedCurrency
            || $verifiedEmail !== $expectedEmail
            || $metadataMismatch
        ) {
            Log::warning('Paystack verification payload failed integrity checks.', [
                'payment_request_id' => $paymentRequest->id,
                'expected_reference' => $reference,
                'verified_reference' => $verifiedReference,
                'expected_amount' => $expectedAmount,
                'verified_amount' => $verifiedAmount,
                'expected_currency' => $expectedCurrency,
                'verified_currency' => $verifiedCurrency,
                'expected_email' => $expectedEmail,
                'verified_email' => $verifiedEmail,
                'expected_request_id' => $paymentRequest->id,
                'verified_request_id' => $verifiedRequestId,
                'expected_public_reference' => $paymentRequest->public_reference,
                'verified_public_reference' => $verifiedPublicReference,
                'expected_payment_type_id' => $paymentRequest->payment_type_id,
                'verified_payment_type_id' => $verifiedPaymentTypeId,
            ]);

            $paymentRequest->forceFill([
                'verification_payload' => ['status' => false, 'message' => 'Integrity check failed', 'data' => $verifiedData],
                'gateway_response' => 'Payment verification could not confirm the expected request details.',
            ])->save();

            throw new DomainException('We could not safely confirm this payment. Please contact support with your request reference.');
        }
    }

    /**
     * @param  array<string, mixed>  $response
     * @param  array<string, mixed>  $data
     */
    protected function applyVerificationResult(
        PaymentRequest $paymentRequest,
        PaymentRequestStatus $status,
        array $response,
        array $data,
    ): string {
        $gatewayResponse = (string) ($data['gateway_response'] ?? $response['message'] ?? 'Paystack verification completed');

        $paymentRequest->forceFill([
            'payment_status' => $status,
            'paystack_reference' => (string) ($data['reference'] ?? $paymentRequest->paystack_reference),
            'payment_channel' => data_get($data, 'channel'),
            'gateway_response' => $gatewayResponse,
            'transaction_reference' => isset($data['id']) ? (string) $data['id'] : $paymentRequest->transaction_reference,
            'verification_payload' => $response,
            'paid_at' => $status === PaymentRequestStatus::Successful
                ? $this->parsePaidAt($data)
                : null,
        ])->save();

        Log::info('Paystack verification completed.', [
            'payment_request_id' => $paymentRequest->id,
            'status' => $status->value,
            'paystack_reference' => $paymentRequest->paystack_reference,
        ]);

        return match ($status) {
            PaymentRequestStatus::Successful => 'Payment verified successfully.',
            PaymentRequestStatus::Failed => 'Payment was not successful.',
            PaymentRequestStatus::Abandoned => 'Payment was abandoned before completion.',
            PaymentRequestStatus::Pending => 'Payment is still pending confirmation.',
        };
    }

    protected function generateInternalReference(): string
    {
        return 'GSUAPR-'.Str::upper((string) Str::ulid());
    }

    protected function generateGatewayReference(string $paymentReference): string
    {
        return $paymentReference;
    }

    protected function callbackUrl(): string
    {
        $configuredCallbackUrl = (string) config('services.paystack.callback_url');

        return $configuredCallbackUrl !== ''
            ? $configuredCallbackUrl
            : route('student-payments.paystack.callback', absolute: true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function checkoutMetadata(PaymentRequest $paymentRequest): array
    {
        return [
            'cancel_action' => $this->cancelActionUrl($paymentRequest),
            'payment_request_id' => $paymentRequest->id,
            'public_reference' => $paymentRequest->public_reference,
            'payment_reference' => $paymentRequest->payment_reference,
            'matric_number' => $paymentRequest->matric_number,
            'payment_type_id' => $paymentRequest->payment_type_id,
            'payment_type_name' => $paymentRequest->payment_type_name,
        ];
    }

    protected function cancelActionUrl(PaymentRequest $paymentRequest): string
    {
        return route('student-payments.paystack.cancel', $paymentRequest, absolute: true);
    }

    protected function preparePaymentRequestForCheckout(PaymentRequest $paymentRequest): PaymentRequest
    {
        $paymentRequest = PaymentRequest::query()
            ->with('paymentType')
            ->findOrFail($paymentRequest->getKey());

        if (! $paymentRequest->canInitializePayment()) {
            throw new DomainException('Only pending payment requests can be sent to Paystack.');
        }

        $paymentType = $paymentRequest->paymentType;

        if (! $paymentType instanceof PaymentType || ! $paymentType->is_active) {
            throw new DomainException('This payment request can no longer be paid because the selected payment type is not active.');
        }

        return $paymentRequest;
    }

    protected function amountInSubunit(string $amount): int
    {
        return (int) str_replace('.', '', number_format((float) $amount, 2, '.', ''));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function applySplitConfig(array $payload, PaymentRequest $paymentRequest): array
    {
        $splitCode = trim((string) config('services.paystack.split_code', ''));
        $subaccount = trim((string) config('services.paystack.subaccount', ''));
        $bearer = trim((string) config('services.paystack.bearer', ''));
        if ($splitCode !== '') {
            $payload['split_code'] = $splitCode;
        } elseif ($subaccount !== '') {
            $payload['subaccount'] = $subaccount;

            $total = (float) $paymentRequest->amount;
            $subaccountAmount = (float) $paymentRequest->base_amount;
            $transactionCharge = max($total - $subaccountAmount, 0);

            if ($transactionCharge > 0) {
                $payload['transaction_charge'] = $this->amountInSubunit((string) $transactionCharge);
            }

            if ($bearer !== '') {
                $payload['bearer'] = $bearer;
            }
        }

        return $payload;
    }

    protected function normalizeGatewayStatus(string $gatewayStatus): PaymentRequestStatus
    {
        return match (strtolower($gatewayStatus)) {
            'success' => PaymentRequestStatus::Successful,
            'failed' => PaymentRequestStatus::Failed,
            'abandoned', 'cancelled', 'canceled' => PaymentRequestStatus::Abandoned,
            default => PaymentRequestStatus::Pending,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function parsePaidAt(array $data): ?CarbonImmutable
    {
        $paidAt = $data['paid_at'] ?? $data['paidAt'] ?? null;

        if (! is_string($paidAt) || trim($paidAt) === '') {
            return now()->toImmutable();
        }

        try {
            return CarbonImmutable::parse($paidAt);
        } catch (RuntimeException) {
            return now()->toImmutable();
        }
    }
}
