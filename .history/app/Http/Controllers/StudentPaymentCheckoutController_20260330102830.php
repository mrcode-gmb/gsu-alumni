<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use App\Services\PaymentCheckoutService;
use App\Services\ReceiptService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use RuntimeException;
use Throwable;

class StudentPaymentCheckoutController extends Controller
{
    public function __construct(
        protected PaymentCheckoutService $paymentCheckoutService,
        protected ReceiptService $receiptService,
    ) {
    }

    public function initialize(Request $request, PaymentRequest $paymentRequest): Response|RedirectResponse
    {
        if ($request->expectsJson()) {
            try {
                $result = $this->paymentCheckoutService->preparePopupPayment($paymentRequest);
            } catch (DomainException $exception) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 422);
            } catch (RuntimeException $exception) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 500);
            } catch (Throwable $exception) {
                Log::error('Unexpected popup checkout preparation error.', [
                    'payment_request_id' => $paymentRequest->id,
                    'message' => $exception->getMessage(),
                ]);

                return response()->json([
                    'message' => 'We could not prepare the Paystack popup right now. Please try again.',
                ], 500);
            }

            return response()->json([
                'checkout' => $result['checkout'],
            ]);
        }

        try {
            $result = $this->paymentCheckoutService->initializePayment($paymentRequest);
        } catch (DomainException|RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            Log::error('Unexpected payment initialization error.', [
                'payment_request_id' => $paymentRequest->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->with('error', 'We could not start the Paystack payment at the moment. Please try again.');
        }

        $authorizationUrl = $result['authorizationUrl'];

        if ($authorizationUrl === '') {
            return back()->with('error', 'Paystack did not return a valid authorization URL.');
        }

        return $request->header('X-Inertia')
            ? Inertia::location($authorizationUrl)
            : redirect()->away($authorizationUrl);
    }

    public function callback(Request $request): RedirectResponse
    {
        $reference = trim((string) ($request->query('reference') ?? $request->query('trxref') ?? ''));
        return $request;
        try {
            $result = $this->paymentCheckoutService->verifyPaymentByReference($reference);
        } catch (DomainException|RuntimeException $exception) {
            return to_route('student-payments.create')
                ->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            Log::error('Unexpected Paystack callback error.', [
                'reference' => $reference,
                'message' => $exception->getMessage(),
            ]);

            return to_route('student-payments.create')
                ->with('error', 'We could not complete payment verification at the moment. Please try again later.');
        }

        return $this->redirectAfterVerification($result);
    }

    public function cancel(PaymentRequest $paymentRequest): RedirectResponse
    {
        try {
            $paymentRequest = $this->paymentCheckoutService->markPaymentAsAbandoned($paymentRequest);
        } catch (Throwable $exception) {
            Log::error('Unexpected Paystack cancellation handling error.', [
                'payment_request_id' => $paymentRequest->id,
                'message' => $exception->getMessage(),
            ]);

            return to_route('student-payments.show', $paymentRequest)
                ->with('error', 'We could not update the payment status after the cancellation.');
        }

        return to_route('student-payments.show', $paymentRequest)
            ->with('error', 'Payment was cancelled before completion.');
    }

    public function verify(PaymentRequest $paymentRequest): RedirectResponse
    {
        try {
            $result = $this->paymentCheckoutService->verifyExistingPaymentRequest($paymentRequest);
        } catch (DomainException|RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            Log::error('Unexpected manual payment verification error.', [
                'payment_request_id' => $paymentRequest->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->with('error', 'We could not verify this payment right now. Please try again later.');
        }

        return $this->redirectAfterVerification($result);
    }

    /**
     * @param  array{paymentRequest: PaymentRequest, message: string}  $result
     */
    protected function redirectAfterVerification(array $result): RedirectResponse
    {
        $paymentRequest = $result['paymentRequest'];

        if (! $paymentRequest->payment_status->isSuccessful()) {
            return to_route('student-payments.show', $paymentRequest)
                ->with('error', $result['message']);
        }

        try {
            $receipt = $this->receiptService->issueForPaymentRequest($paymentRequest);

            return redirect()->to($this->receiptService->signedShowUrl($receipt))
                ->with('success', $result['message']);
        } catch (Throwable $exception) {
            Log::error('Receipt generation after successful verification failed.', [
                'payment_request_id' => $paymentRequest->id,
                'message' => $exception->getMessage(),
            ]);

            return to_route('student-payments.show', $paymentRequest)
                ->with('success', $result['message'].' Receipt generation can be retried from the request page.');
        }
    }
}
