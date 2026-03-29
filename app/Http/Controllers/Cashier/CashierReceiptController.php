<?php

namespace App\Http\Controllers\Cashier;

use App\Enums\PaymentRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cashier\VerifyReceiptRequest;
use App\Models\Receipt;
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

        $receipts = Receipt::query()
            ->with('paymentRequest')
            ->whereHas('paymentRequest', function ($query) use ($matricNumber): void {
                $query->whereRaw('UPPER(matric_number) = ?', [$matricNumber])
                    ->where('payment_status', PaymentRequestStatus::Successful);
            })
            ->orderByDesc('issued_at')
            ->get();

        if ($receipts->isEmpty()) {
            throw ValidationException::withMessages([
                'matric_number' => 'No successful receipts were found for this matric number.',
            ]);
        }

        $verification = $receipts->map(function (Receipt $receipt): array {
            $paymentRequest = $receipt->paymentRequest;

            return [
                'receipt_number' => $receipt->receipt_number,
                'member_name' => $paymentRequest?->full_name ?? 'Not recorded',
                'matric_number' => $paymentRequest?->matric_number ?? 'Not recorded',
                'payment_type' => $paymentRequest?->payment_type_name ?? 'Not recorded',
                'payment_amount' => (string) ($paymentRequest?->base_amount ?? '0.00'),
                'paid_at' => $paymentRequest?->paid_at?->toIso8601String(),
                'payment_reference' => $paymentRequest?->payment_reference,
                'status' => PaymentRequestStatus::Successful->label(),
            ];
        })->values()->all();

        return Inertia::render('cashier/receipt-verify', [
            'verification' => $verification,
        ]);
    }
}
