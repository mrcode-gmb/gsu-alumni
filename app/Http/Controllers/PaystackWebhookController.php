<?php

namespace App\Http\Controllers;

use App\Services\PaymentCheckoutService;
use App\Services\PaystackService;
use App\Services\ReceiptService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PaystackWebhookController extends Controller
{
    public function __construct(
        protected PaystackService $paystackService,
        protected PaymentCheckoutService $paymentCheckoutService,
        protected ReceiptService $receiptService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $signature = (string) $request->header('x-paystack-signature', '');
        $rawPayload = $request->getContent();

        try {
            if (! $this->paystackService->isValidWebhookSignature($rawPayload, $signature)) {
                Log::warning('Rejected Paystack webhook with invalid signature.', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return response()->json([
                    'message' => 'Invalid Paystack signature.',
                ], 403);
            }
        } catch (RuntimeException $exception) {
            Log::error('Paystack webhook signature validation failed because configuration is incomplete.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Webhook verification is not configured.',
            ], 500);
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();

        if ($payload === []) {
            return response()->json([
                'message' => 'Invalid Paystack payload.',
            ], 400);
        }

        try {
            $paymentRequest = $this->paymentCheckoutService->handleWebhookEvent($payload);

            if ($paymentRequest?->payment_status->isSuccessful()) {
                $this->receiptService->issueForPaymentRequest($paymentRequest);
            }
        } catch (DomainException $exception) {
            Log::warning('Paystack webhook event was ignored.', [
                'event' => $payload['event'] ?? null,
                'reference' => data_get($payload, 'data.reference'),
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Webhook received.',
            ]);
        } catch (Throwable $exception) {
            Log::error('Paystack webhook processing failed.', [
                'event' => $payload['event'] ?? null,
                'reference' => data_get($payload, 'data.reference'),
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Webhook processing failed.',
            ], 500);
        }

        return response()->json([
            'message' => 'Webhook processed successfully.',
        ]);
    }
}
