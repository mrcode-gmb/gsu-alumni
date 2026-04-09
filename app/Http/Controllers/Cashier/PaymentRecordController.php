<?php

namespace App\Http\Controllers\Cashier;

use App\Enums\PaymentRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\PaymentRequest;
use App\Services\PaymentCheckoutService;
use App\Services\ReceiptService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use RuntimeException;
use Throwable;

class PaymentRecordController extends Controller
{
    public function __construct(
        protected PaymentCheckoutService $paymentCheckoutService,
        protected ReceiptService $receiptService,
    ) {
    }

    public function index(): RedirectResponse
    {
        return to_route('cashier.receipts.verify');
    }

    public function successful(): RedirectResponse
    {
        return to_route('cashier.receipts.verify');
    }

    public function verify(PaymentRequest $paymentRequest)
    {
        if (! in_array($paymentRequest->payment_status, [PaymentRequestStatus::Pending, PaymentRequestStatus::Abandoned], true)) {
            return back()->with('error', 'Only pending or abandoned payments can be rechecked at this time.');
        }

        if ($paymentRequest->paystack_reference === null && $paymentRequest->payment_reference === null) {
            return back()->with('error', 'This payment request has not been initialized with Paystack yet.');
        }

        try {
            $result = $this->paymentCheckoutService->verifyExistingPaymentRequest($paymentRequest);
        } catch (DomainException|RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            return back()->with('error', 'We could not recheck this payment right now. Please try again shortly.');
        }

        $paymentRequest = $result['paymentRequest'];
        $message = $result['message'];

        if ($paymentRequest->payment_status->isSuccessful()) {
            try {
                $this->receiptService->issueForPaymentRequest($paymentRequest);
            } catch (Throwable $exception) {
                return back()->with('success', $message.' Receipt generation can be retried later.');
            }
        }

        return back()->with('success', $message);
    }

    public function receipt(PaymentRequest $paymentRequest)
    {
        try {
            $receipt = $this->receiptService->issueForPaymentRequest($paymentRequest);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            return back()->with('error', 'We could not open the receipt right now. Please try again.');
        }

        return redirect()->to($this->receiptService->signedShowUrl($receipt));
    }

}
