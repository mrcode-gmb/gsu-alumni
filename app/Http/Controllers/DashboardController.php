<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use App\Services\AdminPaymentRecordService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected AdminPaymentRecordService $adminPaymentRecordService,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        $user = request()->user();

        if (! $user?->isAdmin()) {
            if ($user?->isCashier()) {
                return to_route('cashier.receipts.verify');
            }

            return Inertia::render('dashboard', [
                'adminSummary' => null,
                'cashierSummary' => null,
                'successfulTransactionsByProgramType' => [],
                'recentPaymentRecords' => [],
            ]);
        }

        return Inertia::render('dashboard', [
            'adminSummary' => $this->adminPaymentRecordService->dashboardSummary(),
            'cashierSummary' => null,
            'successfulTransactionsByProgramType' => $this->adminPaymentRecordService->successfulTransactionsByProgramType(),
            'recentPaymentRecords' => $this->adminPaymentRecordService
                ->recentRecords()
                ->map(fn (PaymentRequest $paymentRequest): array => $this->dashboardRecordPayload($paymentRequest))
                ->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function dashboardRecordPayload(PaymentRequest $paymentRequest): array
    {
        $receipt = $paymentRequest->receipt;
        $recordedAt = $paymentRequest->paid_at ?? $paymentRequest->created_at;

        return [
            'public_reference' => $paymentRequest->public_reference,
            'full_name' => $paymentRequest->full_name,
            'matric_number' => $paymentRequest->matric_number,
            'payment_type_name' => $paymentRequest->payment_type_name,
            'amount' => $paymentRequest->base_amount,
            'payment_status' => $paymentRequest->payment_status->value,
            'payment_status_label' => $paymentRequest->payment_status->label(),
            'payment_reference' => $paymentRequest->payment_reference,
            'receipt_number' => $receipt?->receipt_number,
            'recorded_at' => $recordedAt?->toIso8601String(),
            'receipt_action_available' => $paymentRequest->payment_status->isSuccessful(),
        ];
    }
}
