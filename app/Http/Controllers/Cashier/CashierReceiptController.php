<?php

namespace App\Http\Controllers\Cashier;

use App\Enums\PaymentRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cashier\VerifyReceiptRequest;
use App\Models\PaymentRequest;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CashierReceiptController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('cashier/receipt-verify', [
            'verification' => null,
        ]);
    }

    public function verify(VerifyReceiptRequest $request): Response
    {
        $matricNumber = strtoupper(trim((string) $request->validated('matric_number')));

        $paymentRequests = PaymentRequest::query()
            ->with('receipt')
            ->whereRaw('UPPER(matric_number) = ?', [$matricNumber])
            ->orderByDesc('created_at')
            ->get();

        if ($paymentRequests->isEmpty()) {
            throw ValidationException::withMessages([
                'matric_number' => 'No payment requests were found for this matric number.',
            ]);
        }

        $verification = $paymentRequests->map(function (PaymentRequest $paymentRequest): array {
            $receipt = $paymentRequest->receipt;

            return [
                'public_reference' => $paymentRequest->public_reference,
                'receipt_number' => $receipt?->receipt_number,
                'member_name' => $paymentRequest->full_name ?? 'Not recorded',
                'matric_number' => $paymentRequest->matric_number ?? 'Not recorded',
                'payment_type' => $paymentRequest->payment_type_name ?? 'Not recorded',
                'payment_amount' => (string) ($paymentRequest->base_amount ?? '0.00'),
                'paid_at' => $paymentRequest->paid_at?->toIso8601String(),
                'payment_reference' => $paymentRequest->payment_reference,
                'status' => $paymentRequest->payment_status->label(),
                'payment_status' => $paymentRequest->payment_status->value,
                'can_recheck' => in_array($paymentRequest->payment_status, [PaymentRequestStatus::Pending, PaymentRequestStatus::Abandoned], true)
                    && $paymentRequest->initialization_payload !== null
                    && ($paymentRequest->paystack_reference !== null || $paymentRequest->payment_reference !== null),
            ];
        })->values()->all();

        return Inertia::render('cashier/receipt-verify', [
            'verification' => $verification,
        ]);
    }
}
