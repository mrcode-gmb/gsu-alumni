<?php

namespace App\Http\Controllers\Cashier;

use App\Enums\PaymentRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\PaymentRequest;
use App\Services\AdminPaymentRecordService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class PaymentRecordController extends Controller
{
    public function __construct(
        protected AdminPaymentRecordService $adminPaymentRecordService,
    ) {
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $query = PaymentRequest::query()
            ->with(['receipt:id,payment_request_id,receipt_number'])
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('matric_number', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhere('paystack_reference', 'like', "%{$search}%")
                    ->orWhereHas('receipt', function ($receiptQuery) use ($search) {
                        $receiptQuery->where('receipt_number', 'like', "%{$search}%");
                    });
            });
        }

        $paymentRecords = $query->paginate(20)->withQueryString();
        $paymentRecords->setCollection(
            $paymentRecords->getCollection()->map(
                fn (PaymentRequest $paymentRequest): array => $this->paymentRecordListPayload($paymentRequest),
            ),
        );

        return Inertia::render('cashier/payment-records/index', [
            'summary' => $this->adminPaymentRecordService->cashierDashboardSummary(),
            'paymentRecords' => $this->paginationPayload($paymentRecords),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function paymentRecordListPayload(PaymentRequest $paymentRequest): array
    {
        $receipt = $paymentRequest->receipt;
        $recordedAt = $paymentRequest->paid_at ?? $paymentRequest->created_at;

        return [
            'public_reference' => $paymentRequest->public_reference,
            'full_name' => $paymentRequest->full_name,
            'matric_number' => $paymentRequest->matric_number,
            'payment_type_name' => $paymentRequest->payment_type_name,
            'base_amount' => $paymentRequest->base_amount,
            'payment_status' => $paymentRequest->payment_status->value,
            'payment_status_label' => $paymentRequest->payment_status->label(),
            'payment_reference' => $paymentRequest->payment_reference,
            'receipt_number' => $receipt?->receipt_number,
            'recorded_at' => $recordedAt?->toIso8601String(),
            'is_successful' => $paymentRequest->payment_status === PaymentRequestStatus::Successful,
        ];
    }

    /**
     * @param  LengthAwarePaginator<int, array<string, mixed>>  $paginator
     * @return array<string, mixed>
     */
    protected function paginationPayload(LengthAwarePaginator $paginator): array
    {
        $payload = $paginator->toArray();

        return [
            'data' => $payload['data'],
            'links' => $payload['links'],
            'meta' => [
                'current_page' => $payload['current_page'],
                'from' => $payload['from'],
                'last_page' => $payload['last_page'],
                'path' => $payload['path'],
                'per_page' => $payload['per_page'],
                'to' => $payload['to'],
                'total' => $payload['total'],
            ],
        ];
    }
}
