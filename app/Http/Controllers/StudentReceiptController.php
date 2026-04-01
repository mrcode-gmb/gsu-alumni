<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
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
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
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
        $receipt = $this->receiptService->findLatestByEmailAndMatric(
            $request->string('email')->toString(),
            $request->string('matric_number')->toString(),
        );

        if (! $receipt) {
            throw ValidationException::withMessages([
                'email' => 'No matching successful receipt was found for the supplied details.',
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

        return Inertia::render('student-receipts/show', [
            'receipt' => $this->receiptService->payload($receipt),
            'downloadUrl' => $this->receiptService->signedDownloadUrl($receipt),
        ]);
    }

    public function download(Receipt $receipt): HttpFoundationResponse
    {
        $receipt->loadMissing('paymentRequest');

        abort_unless($receipt->paymentRequest?->payment_status->isSuccessful(), 404);

        $payload = $this->receiptService->payload($receipt);
        $pdf = Pdf::loadView('pdf.receipt', [
            'receipt' => $payload,
            'logoDataUri' => $this->receiptService->logoDataUri(),
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download($this->receiptService->downloadFilename($receipt));
    }
}
