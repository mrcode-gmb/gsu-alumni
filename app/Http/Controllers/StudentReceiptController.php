<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentReceipts\LookupReceiptRequest;
use App\Models\PaymentRequest;
use App\Models\Receipt;
use App\Services\ReceiptService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class StudentReceiptController extends Controller
{
    public function __construct(
        protected ReceiptService $receiptService,
    ) {
    }

    public function lookupForm(): Response
    {
        return Inertia::render('student-receipts/lookup');
    }

    public function lookup(LookupReceiptRequest $request): RedirectResponse
    {
        $receipt = $this->receiptService->findByReceiptNumberAndMatric(
            $request->string('receipt_number')->toString(),
            $request->string('matric_number')->toString(),
        );

        if (! $receipt) {
            throw ValidationException::withMessages([
                'receipt_number' => 'No matching successful receipt was found for the supplied details.',
            ]);
        }

        return redirect()->to($this->receiptService->signedShowUrl($receipt))
            ->with('success', 'Receipt located successfully.');
    }

    public function createFromPaymentRequest(PaymentRequest $paymentRequest): RedirectResponse
    {
        try {
            $receipt = $this->receiptService->issueForPaymentRequest($paymentRequest);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            Log::error('Unexpected receipt generation error.', [
                'payment_request_id' => $paymentRequest->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->with('error', 'We could not prepare the receipt right now. Please try again shortly.');
        }

        return redirect()->to($this->receiptService->signedShowUrl($receipt))
            ->with('success', 'Your receipt is ready.');
    }

    public function show(Receipt $receipt): Response
    {
        $receipt->loadMissing('paymentRequest');

        abort_unless($receipt->paymentRequest?->payment_status->isSuccessful(), 404);

        $snapshot = is_array($receipt->snapshot) ? $receipt->snapshot : [];

        return Inertia::render('student-receipts/show', [
            'receipt' => [
                'public_reference' => $receipt->public_reference,
                'receipt_number' => $receipt->receipt_number,
                'issued_at' => $receipt->issued_at?->toIso8601String(),
                'official_note' => $receipt->official_note,
                'payment_request_public_reference' => (string) ($snapshot['payment_request_public_reference'] ?? $receipt->paymentRequest->public_reference),
                'payment_date' => $snapshot['payment_date'] ?? $receipt->paymentRequest->paid_at?->toIso8601String(),
                'full_name' => (string) ($snapshot['full_name'] ?? $receipt->paymentRequest->full_name),
                'matric_number' => (string) ($snapshot['matric_number'] ?? $receipt->paymentRequest->matric_number),
                'email' => (string) ($snapshot['email'] ?? $receipt->paymentRequest->email),
                'phone_number' => (string) ($snapshot['phone_number'] ?? $receipt->paymentRequest->phone_number),
                'department' => (string) ($snapshot['department'] ?? $receipt->paymentRequest->department),
                'faculty' => (string) ($snapshot['faculty'] ?? $receipt->paymentRequest->faculty),
                'program_type_name' => $snapshot['program_type_name'] ?? $receipt->paymentRequest->program_type_name,
                'graduation_session' => (string) ($snapshot['graduation_session'] ?? $receipt->paymentRequest->graduation_session),
                'payment_type_name' => (string) ($snapshot['payment_type_name'] ?? $receipt->paymentRequest->payment_type_name),
                'base_amount' => (string) ($snapshot['base_amount'] ?? $receipt->paymentRequest->base_amount),
                'portal_charge_amount' => (string) ($snapshot['portal_charge_amount'] ?? $receipt->paymentRequest->portal_charge_amount),
                'paystack_charge_amount' => (string) ($snapshot['paystack_charge_amount'] ?? $receipt->paymentRequest->paystack_charge_amount),
                'amount' => (string) ($snapshot['amount'] ?? $receipt->paymentRequest->amount),
                'payment_status' => (string) ($snapshot['payment_status'] ?? $receipt->paymentRequest->payment_status->value),
                'payment_status_label' => (string) ($snapshot['payment_status_label'] ?? $receipt->paymentRequest->payment_status->label()),
                'payment_reference' => $snapshot['payment_reference'] ?? $receipt->paymentRequest->payment_reference,
                'paystack_reference' => $snapshot['paystack_reference'] ?? $receipt->paymentRequest->paystack_reference,
                'payment_channel' => $snapshot['payment_channel'] ?? $receipt->paymentRequest->payment_channel,
                'transaction_reference' => $snapshot['transaction_reference'] ?? $receipt->paymentRequest->transaction_reference,
            ],
        ]);
    }
}
