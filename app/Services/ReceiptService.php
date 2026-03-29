<?php

namespace App\Services;

use App\Models\PaymentRequest;
use App\Models\Receipt;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

class ReceiptService
{
    public function issueForPaymentRequest(PaymentRequest $paymentRequest): Receipt
    {
        return DB::transaction(function () use ($paymentRequest): Receipt {
            $paymentRequest = PaymentRequest::query()
                ->lockForUpdate()
                ->findOrFail($paymentRequest->getKey());

            if (! $paymentRequest->payment_status->isSuccessful()) {
                throw new DomainException('Receipts are only available after a payment has been verified successfully.');
            }

            $existingReceipt = Receipt::query()
                ->where('payment_request_id', $paymentRequest->getKey())
                ->first();

            if ($existingReceipt) {
                return $existingReceipt->loadMissing('paymentRequest');
            }

            $receipt = Receipt::create([
                'payment_request_id' => $paymentRequest->getKey(),
                'public_reference' => (string) Str::ulid(),
                'receipt_number' => $this->generateReceiptNumber(),
                'issued_at' => now(),
                'official_note' => 'This is evidence of payment.',
                'snapshot' => $this->buildSnapshot($paymentRequest),
            ]);

            Log::info('Receipt issued for successful payment request.', [
                'payment_request_id' => $paymentRequest->id,
                'receipt_id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
            ]);

            return $receipt->load('paymentRequest');
        });
    }

    public function findByReceiptNumberAndMatric(string $receiptNumber, string $matricNumber): ?Receipt
    {
        $receiptNumber = strtoupper(trim($receiptNumber));
        $matricNumber = strtoupper($this->normalizeText($matricNumber));

        if ($receiptNumber === '' || $matricNumber === '') {
            return null;
        }

        return Receipt::query()
            ->with('paymentRequest')
            ->where('receipt_number', $receiptNumber)
            ->whereHas('paymentRequest', function ($query) use ($matricNumber): void {
                $query
                    ->where('matric_number', $matricNumber)
                    ->where('payment_status', 'successful');
            })
            ->first();
    }

    public function signedShowUrl(Receipt $receipt): string
    {
        return URL::signedRoute('student-receipts.show', [
            'receipt' => $receipt,
        ]);
    }

    public function signedDownloadUrl(Receipt $receipt): string
    {
        return URL::signedRoute('student-receipts.download', [
            'receipt' => $receipt,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(Receipt $receipt): array
    {
        $receipt->loadMissing('paymentRequest');

        $paymentRequest = $receipt->paymentRequest;
        $snapshot = is_array($receipt->snapshot) ? $receipt->snapshot : [];

        return [
            'public_reference' => $receipt->public_reference,
            'receipt_number' => $receipt->receipt_number,
            'issued_at' => $receipt->issued_at?->toIso8601String(),
            'official_note' => $receipt->official_note,
            'payment_request_public_reference' => (string) ($snapshot['payment_request_public_reference'] ?? $paymentRequest?->public_reference),
            'payment_date' => $snapshot['payment_date'] ?? $paymentRequest?->paid_at?->toIso8601String(),
            'full_name' => (string) ($snapshot['full_name'] ?? $paymentRequest?->full_name),
            'matric_number' => (string) ($snapshot['matric_number'] ?? $paymentRequest?->matric_number),
            'email' => (string) ($snapshot['email'] ?? $paymentRequest?->email),
            'phone_number' => (string) ($snapshot['phone_number'] ?? $paymentRequest?->phone_number),
            'department' => (string) ($snapshot['department'] ?? $paymentRequest?->department),
            'faculty' => (string) ($snapshot['faculty'] ?? $paymentRequest?->faculty),
            'program_type_name' => $snapshot['program_type_name'] ?? $paymentRequest?->program_type_name,
            'graduation_session' => (string) ($snapshot['graduation_session'] ?? $paymentRequest?->graduation_session),
            'payment_type_name' => (string) ($snapshot['payment_type_name'] ?? $paymentRequest?->payment_type_name),
            'base_amount' => (string) ($snapshot['base_amount'] ?? $paymentRequest?->base_amount),
            'portal_charge_amount' => (string) ($snapshot['portal_charge_amount'] ?? $paymentRequest?->portal_charge_amount),
            'paystack_charge_amount' => (string) ($snapshot['paystack_charge_amount'] ?? $paymentRequest?->paystack_charge_amount),
            'amount' => (string) ($snapshot['amount'] ?? $paymentRequest?->amount),
            'payment_status' => (string) ($snapshot['payment_status'] ?? $paymentRequest?->payment_status->value),
            'payment_status_label' => (string) ($snapshot['payment_status_label'] ?? $paymentRequest?->payment_status->label()),
            'payment_reference' => $snapshot['payment_reference'] ?? $paymentRequest?->payment_reference,
            'paystack_reference' => $snapshot['paystack_reference'] ?? $paymentRequest?->paystack_reference,
            'payment_channel' => $snapshot['payment_channel'] ?? $paymentRequest?->payment_channel,
            'transaction_reference' => $snapshot['transaction_reference'] ?? $paymentRequest?->transaction_reference,
        ];
    }

    public function downloadFilename(Receipt $receipt): string
    {
        return 'GSU-Receipt-'.$receipt->receipt_number.'.pdf';
    }

    public function logoDataUri(): ?string
    {
        return $this->fileDataUri(public_path('images-removebg-preview.png'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSnapshot(PaymentRequest $paymentRequest): array
    {
        return [
            'payment_request_public_reference' => $paymentRequest->public_reference,
            'payment_date' => $paymentRequest->paid_at?->toIso8601String(),
            'full_name' => $paymentRequest->full_name,
            'matric_number' => $paymentRequest->matric_number,
            'email' => $paymentRequest->email,
            'phone_number' => $paymentRequest->phone_number,
            'department' => $paymentRequest->department,
            'faculty' => $paymentRequest->faculty,
            'program_type_name' => $paymentRequest->program_type_name,
            'graduation_session' => $paymentRequest->graduation_session,
            'payment_type_name' => $paymentRequest->payment_type_name,
            'base_amount' => (string) $paymentRequest->base_amount,
            'portal_charge_amount' => (string) $paymentRequest->portal_charge_amount,
            'paystack_charge_amount' => (string) $paymentRequest->paystack_charge_amount,
            'amount' => (string) $paymentRequest->amount,
            'payment_status' => $paymentRequest->payment_status->value,
            'payment_status_label' => $paymentRequest->payment_status->label(),
            'payment_reference' => $paymentRequest->payment_reference,
            'paystack_reference' => $paymentRequest->paystack_reference,
            'payment_channel' => $paymentRequest->payment_channel,
            'transaction_reference' => $paymentRequest->transaction_reference,
        ];
    }

    protected function generateReceiptNumber(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $receiptNumber = 'GSU-RCP-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));

            if (! Receipt::query()->where('receipt_number', $receiptNumber)->exists()) {
                return $receiptNumber;
            }
        }

        throw new RuntimeException('We could not generate a unique receipt number. Please try again.');
    }

    protected function normalizeText(string $value): string
    {
        return preg_replace('/\s+/', ' ', trim($value)) ?? '';
    }

    protected function fileDataUri(string $path): ?string
    {
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $mimeType = mime_content_type($path) ?: 'image/png';
        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        return sprintf('data:%s;base64,%s', $mimeType, base64_encode($contents));
    }
}
