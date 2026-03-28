<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentRecords\FilterPaymentRecordRequest;
use App\Models\PaymentRequest;
use App\Models\PaymentType;
use App\Services\AdminPaymentRecordService;
use App\Services\ReceiptService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class PaymentRecordController extends Controller
{
    public function __construct(
        protected AdminPaymentRecordService $adminPaymentRecordService,
        protected ReceiptService $receiptService,
    ) {
    }

    public function index(FilterPaymentRecordRequest $request): Response
    {
        $filters = $this->adminPaymentRecordService->normalizeFilters($request->validated());
        $paymentRecords = $this->adminPaymentRecordService->paginateRecords($filters);
        $paymentRecords->setCollection(
            $paymentRecords->getCollection()->map(
                fn (PaymentRequest $paymentRequest): array => $this->paymentRecordListPayload($paymentRequest),
            ),
        );

        return Inertia::render('admin/payment-records/index', [
            'summary' => $this->adminPaymentRecordService->dashboardSummary(),
            'paymentRecords' => $this->paginationPayload($paymentRecords),
            'filters' => $filters,
            'filterOptions' => $this->adminPaymentRecordService->filterOptions(),
            'activeFilters' => $this->activeFiltersPayload($filters),
        ]);
    }

    public function show(PaymentRequest $paymentRequest): Response
    {
        $paymentRequest->loadMissing('receipt');

        return Inertia::render('admin/payment-records/show', [
            'paymentRecord' => $this->paymentRecordDetailPayload($paymentRequest),
        ]);
    }

    public function print(FilterPaymentRecordRequest $request): Response
    {
        $filters = $this->adminPaymentRecordService->normalizeFilters($request->validated());
        $result = $this->adminPaymentRecordService->printableRecords($filters);

        return Inertia::render('admin/payment-records/print-index', [
            'summary' => $this->adminPaymentRecordService->dashboardSummary(),
            'paymentRecords' => $result['records']
                ->map(fn (PaymentRequest $paymentRequest): array => $this->paymentRecordListPayload($paymentRequest))
                ->values(),
            'filters' => $filters,
            'activeFilters' => $this->activeFiltersPayload($filters),
            'printMeta' => [
                'total' => $result['total'],
                'truncated' => $result['truncated'],
                'limit' => $result['limit'],
            ],
        ]);
    }

    public function printSingle(PaymentRequest $paymentRequest): Response
    {
        $paymentRequest->loadMissing('receipt');

        return Inertia::render('admin/payment-records/print-show', [
            'paymentRecord' => $this->paymentRecordDetailPayload($paymentRequest),
        ]);
    }

    public function receipt(PaymentRequest $paymentRequest): RedirectResponse
    {
        try {
            $receipt = $this->receiptService->issueForPaymentRequest($paymentRequest);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->to($this->receiptService->signedShowUrl($receipt));
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<int, array{label: string, value: string}>
     */
    protected function activeFiltersPayload(array $filters): array
    {
        $paymentTypeLabel = ($filters['payment_type_id'] ?? '') !== ''
            ? PaymentType::query()->whereKey($filters['payment_type_id'])->value('name')
            : null;
        $statusLabel = ($filters['payment_status'] ?? '') !== ''
            ? PaymentRequestStatus::tryFrom($filters['payment_status'])?->label()
            : null;

        return collect([
            ($filters['search'] ?? '') !== '' ? ['label' => 'Search', 'value' => $filters['search']] : null,
            $paymentTypeLabel ? ['label' => 'Payment type', 'value' => $paymentTypeLabel] : null,
            $statusLabel ? ['label' => 'Status', 'value' => $statusLabel] : null,
            ($filters['department'] ?? '') !== '' ? ['label' => 'Department', 'value' => $filters['department']] : null,
            ($filters['faculty'] ?? '') !== '' ? ['label' => 'Faculty', 'value' => $filters['faculty']] : null,
            ($filters['graduation_session'] ?? '') !== '' ? ['label' => 'Graduation session', 'value' => $filters['graduation_session']] : null,
            ($filters['date_from'] ?? '') !== '' ? ['label' => 'Date from', 'value' => $filters['date_from']] : null,
            ($filters['date_to'] ?? '') !== '' ? ['label' => 'Date to', 'value' => $filters['date_to']] : null,
        ])->filter()->values()->all();
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
            'email' => $paymentRequest->email,
            'department' => $paymentRequest->department,
            'faculty' => $paymentRequest->faculty,
            'graduation_session' => $paymentRequest->graduation_session,
            'payment_type_name' => $paymentRequest->payment_type_name,
            'amount' => $paymentRequest->amount,
            'payment_status' => $paymentRequest->payment_status->value,
            'payment_status_label' => $paymentRequest->payment_status->label(),
            'payment_reference' => $paymentRequest->payment_reference,
            'receipt_number' => $receipt?->receipt_number,
            'recorded_at' => $recordedAt?->toIso8601String(),
            'has_receipt' => $receipt !== null,
            'can_issue_receipt' => $paymentRequest->payment_status->isSuccessful() && $receipt === null,
            'can_open_receipt' => $paymentRequest->payment_status->isSuccessful(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function paymentRecordDetailPayload(PaymentRequest $paymentRequest): array
    {
        $receipt = $paymentRequest->receipt;

        return [
            'public_reference' => $paymentRequest->public_reference,
            'full_name' => $paymentRequest->full_name,
            'matric_number' => $paymentRequest->matric_number,
            'email' => $paymentRequest->email,
            'phone_number' => $paymentRequest->phone_number,
            'department' => $paymentRequest->department,
            'faculty' => $paymentRequest->faculty,
            'graduation_session' => $paymentRequest->graduation_session,
            'payment_type_name' => $paymentRequest->payment_type_name,
            'payment_type_description' => $paymentRequest->payment_type_description,
            'base_amount' => $paymentRequest->base_amount,
            'portal_charge_amount' => $paymentRequest->portal_charge_amount,
            'paystack_charge_amount' => $paymentRequest->paystack_charge_amount,
            'amount' => $paymentRequest->amount,
            'payment_status' => $paymentRequest->payment_status->value,
            'payment_status_label' => $paymentRequest->payment_status->label(),
            'payment_reference' => $paymentRequest->payment_reference,
            'paystack_reference' => $paymentRequest->paystack_reference,
            'transaction_reference' => $paymentRequest->transaction_reference,
            'payment_channel' => $paymentRequest->payment_channel,
            'gateway_response' => $paymentRequest->gateway_response,
            'paid_at' => $paymentRequest->paid_at?->toIso8601String(),
            'created_at' => $paymentRequest->created_at?->toIso8601String(),
            'updated_at' => $paymentRequest->updated_at?->toIso8601String(),
            'receipt_number' => $receipt?->receipt_number,
            'receipt_public_reference' => $receipt?->public_reference,
            'receipt_issued_at' => $receipt?->issued_at?->toIso8601String(),
            'has_receipt' => $receipt !== null,
            'can_issue_receipt' => $paymentRequest->payment_status->isSuccessful() && $receipt === null,
            'can_open_receipt' => $paymentRequest->payment_status->isSuccessful(),
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
