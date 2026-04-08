<?php

namespace App\Services;

use App\Enums\PaymentRequestStatus;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\PaymentRequest;
use App\Models\PaymentType;
use App\Models\ProgramType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AdminPaymentRecordService
{
    /**
     * @return array<string, string>
     */
    public function sortOptions(): array
    {
        return [
            'newest' => 'Newest first',
            'oldest' => 'Oldest first',
            'amount_desc' => 'Amount: highest first',
            'amount_asc' => 'Amount: lowest first',
            'name_asc' => 'Student name: A to Z',
            'name_desc' => 'Student name: Z to A',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, string>
     */
    public function normalizeFilters(array $validated): array
    {
        $sort = (string) ($validated['sort'] ?? 'newest');
        $sort = array_key_exists($sort, $this->sortOptions()) ? $sort : 'newest';

        return [
            'search' => trim((string) ($validated['search'] ?? '')),
            'payment_type_id' => isset($validated['payment_type_id']) ? (string) $validated['payment_type_id'] : '',
            'payment_status' => trim((string) ($validated['payment_status'] ?? '')),
            'department' => trim((string) ($validated['department'] ?? '')),
            'faculty' => trim((string) ($validated['faculty'] ?? '')),
            'graduation_session' => trim((string) ($validated['graduation_session'] ?? '')),
            'date_from' => trim((string) ($validated['date_from'] ?? '')),
            'date_to' => trim((string) ($validated['date_to'] ?? '')),
            'sort' => $sort,
        ];
    }

    /**
     * @return array<string, int|string>
     */
    public function dashboardSummary(): array
    {
        $summary = PaymentRequest::query()
            ->selectRaw('COUNT(*) as total_payment_requests')
            ->selectRaw(
                'SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as total_successful_payments',
                [PaymentRequestStatus::Successful->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as total_pending_payments',
                [PaymentRequestStatus::Pending->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as total_failed_payments',
                [PaymentRequestStatus::Failed->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as total_abandoned_payments',
                [PaymentRequestStatus::Abandoned->value],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN payment_status = ? THEN amount ELSE 0 END), 0) as total_amount_collected',
                [PaymentRequestStatus::Successful->value],
            )
            ->first();

        return [
            'total_payment_requests' => (int) ($summary?->total_payment_requests ?? 0),
            'total_successful_payments' => (int) ($summary?->total_successful_payments ?? 0),
            'total_pending_payments' => (int) ($summary?->total_pending_payments ?? 0),
            'total_failed_payments' => (int) ($summary?->total_failed_payments ?? 0),
            'total_abandoned_payments' => (int) ($summary?->total_abandoned_payments ?? 0),
            'total_amount_collected' => number_format((float) ($summary?->total_amount_collected ?? 0), 2, '.', ''),
        ];
    }

    /**
     * @return array<string, int|string>
     */
    public function cashierDashboardSummary(): array
    {
        $summary = PaymentRequest::query()
            ->selectRaw('COUNT(*) as total_payment_requests')
            ->selectRaw(
                'SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as total_successful_payments',
                [PaymentRequestStatus::Successful->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as total_pending_payments',
                [PaymentRequestStatus::Pending->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as total_failed_payments',
                [PaymentRequestStatus::Failed->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as total_abandoned_payments',
                [PaymentRequestStatus::Abandoned->value],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN payment_status = ? THEN base_amount ELSE 0 END), 0) as total_amount_collected',
                [PaymentRequestStatus::Successful->value],
            )
            ->first();

        return [
            'total_payment_requests' => (int) ($summary?->total_payment_requests ?? 0),
            'total_successful_payments' => (int) ($summary?->total_successful_payments ?? 0),
            'total_pending_payments' => (int) ($summary?->total_pending_payments ?? 0),
            'total_failed_payments' => (int) ($summary?->total_failed_payments ?? 0),
            'total_abandoned_payments' => (int) ($summary?->total_abandoned_payments ?? 0),
            'total_amount_collected' => number_format((float) ($summary?->total_amount_collected ?? 0), 2, '.', ''),
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, int|string>
     */
    public function cashierSummaryForFilters(array $filters): array
    {
        $summary = $this->cashierFilteredBaseQuery($filters)
            ->selectRaw('COUNT(*) as total_payment_requests')
            ->selectRaw(
                'SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as total_successful_payments',
                [PaymentRequestStatus::Successful->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as total_pending_payments',
                [PaymentRequestStatus::Pending->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as total_failed_payments',
                [PaymentRequestStatus::Failed->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as total_abandoned_payments',
                [PaymentRequestStatus::Abandoned->value],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN payment_status = ? THEN base_amount ELSE 0 END), 0) as total_amount_collected',
                [PaymentRequestStatus::Successful->value],
            )
            ->first();

        return [
            'total_payment_requests' => (int) ($summary?->total_payment_requests ?? 0),
            'total_successful_payments' => (int) ($summary?->total_successful_payments ?? 0),
            'total_pending_payments' => (int) ($summary?->total_pending_payments ?? 0),
            'total_failed_payments' => (int) ($summary?->total_failed_payments ?? 0),
            'total_abandoned_payments' => (int) ($summary?->total_abandoned_payments ?? 0),
            'total_amount_collected' => number_format((float) ($summary?->total_amount_collected ?? 0), 2, '.', ''),
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, int|string>
     */
    public function summaryForFilters(array $filters): array
    {
        $summary = $this->filteredBaseQuery($filters)
            ->selectRaw('COUNT(*) as total_payment_requests')
            ->selectRaw(
                'SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as total_successful_payments',
                [PaymentRequestStatus::Successful->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as total_pending_payments',
                [PaymentRequestStatus::Pending->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as total_failed_payments',
                [PaymentRequestStatus::Failed->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as total_abandoned_payments',
                [PaymentRequestStatus::Abandoned->value],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN payment_status = ? THEN amount ELSE 0 END), 0) as total_amount_collected',
                [PaymentRequestStatus::Successful->value],
            )
            ->first();

        return [
            'total_payment_requests' => (int) ($summary?->total_payment_requests ?? 0),
            'total_successful_payments' => (int) ($summary?->total_successful_payments ?? 0),
            'total_pending_payments' => (int) ($summary?->total_pending_payments ?? 0),
            'total_failed_payments' => (int) ($summary?->total_failed_payments ?? 0),
            'total_abandoned_payments' => (int) ($summary?->total_abandoned_payments ?? 0),
            'total_amount_collected' => number_format((float) ($summary?->total_amount_collected ?? 0), 2, '.', ''),
        ];
    }

    /**
     * @return Collection<int, PaymentRequest>
     */
    public function recentRecords(int $limit = 6): Collection
    {
        return PaymentRequest::query()
            ->with(['receipt:id,payment_request_id,receipt_number,issued_at'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<int, array{name: string, successful_transactions: int}>
     */
    public function successfulTransactionsByProgramType(): array
    {
        $successfulCounts = PaymentRequest::query()
            ->where('payment_status', PaymentRequestStatus::Successful)
            ->selectRaw('program_type_name, COUNT(*) as total')
            ->groupBy('program_type_name')
            ->pluck('total', 'program_type_name');

        $officialProgramTypes = ProgramType::query()
            ->ordered()
            ->get(['name'])
            ->map(fn (ProgramType $programType): array => [
                'name' => $programType->name,
                'successful_transactions' => (int) ($successfulCounts[$programType->name] ?? 0),
            ]);

        $officialProgramTypeNames = $officialProgramTypes->pluck('name');

        $legacyProgramTypes = $successfulCounts
            ->filter(fn (mixed $count, mixed $name): bool => filled($name) && ! $officialProgramTypeNames->contains((string) $name))
            ->map(fn (mixed $count, mixed $name): array => [
                'name' => (string) $name,
                'successful_transactions' => (int) $count,
            ])
            ->values();

        return $officialProgramTypes
            ->concat($legacyProgramTypes)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $filters
     */
    public function paginateRecords(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, string>  $filters
     */
    public function paginateCashierRecords(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->cashierFilteredQuery($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, string>  $filters
     * @return array{records: Collection<int, PaymentRequest>, total: int, truncated: bool, limit: int}
     */
    public function printableRecords(array $filters, int $limit = 250): array
    {
        $query = $this->filteredQuery($filters);
        $total = (clone $query)->count();

        return [
            'records' => $query->limit($limit)->get(),
            'total' => $total,
            'truncated' => $total > $limit,
            'limit' => $limit,
        ];
    }

    /**
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    public function filterOptions(): array
    {
        $officialFaculties = Faculty::query()
            ->ordered()
            ->get(['name'])
            ->map(fn (Faculty $faculty): array => [
                'value' => $faculty->name,
                'label' => $faculty->name,
            ])
            ->values();

        $officialFacultyNames = $officialFaculties->pluck('value');
        $legacyFaculties = PaymentRequest::query()
            ->select('faculty')
            ->distinct()
            ->orderBy('faculty')
            ->pluck('faculty')
            ->filter(fn (?string $faculty): bool => filled($faculty) && ! $officialFacultyNames->contains($faculty))
            ->map(fn (string $faculty): array => [
                'value' => $faculty,
                'label' => $faculty,
            ])
            ->values();
        $officialDepartments = Department::query()
            ->with('faculty:id,name')
            ->ordered()
            ->get()
            ->map(fn (Department $department): array => [
                'value' => $department->name,
                'label' => $department->name,
            ])
            ->values();
        $officialDepartmentNames = $officialDepartments->pluck('value');
        $legacyDepartments = PaymentRequest::query()
            ->select('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->filter(fn (?string $department): bool => filled($department) && ! $officialDepartmentNames->contains($department))
            ->map(fn (string $department): array => [
                'value' => $department,
                'label' => $department,
            ])
            ->values();

        return [
            'paymentTypes' => PaymentType::query()
                ->ordered()
                ->get(['id', 'name'])
                ->map(fn (PaymentType $paymentType): array => [
                    'value' => (string) $paymentType->id,
                    'label' => $paymentType->name,
                ])
                ->values()
                ->all(),
            'paymentStatuses' => collect(PaymentRequestStatus::cases())
                ->map(fn (PaymentRequestStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->values()
                ->all(),
            'departments' => $officialDepartments
                ->concat($legacyDepartments)
                ->values()
                ->all(),
            'faculties' => $officialFaculties
                ->concat($legacyFaculties)
                ->values()
                ->all(),
            'graduationSessions' => PaymentRequest::query()
                ->select('graduation_session')
                ->distinct()
                ->orderByDesc('graduation_session')
                ->pluck('graduation_session')
                ->map(fn (string $graduationSession): array => [
                    'value' => $graduationSession,
                    'label' => $graduationSession,
                ])
                ->values()
                ->all(),
            'sorts' => collect($this->sortOptions())
                ->map(fn (string $label, string $value): array => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, string>  $filters
     */
    protected function filteredQuery(array $filters): Builder
    {
        return $this->applySort(
            $this->filteredBaseQuery($filters)->with(['receipt:id,payment_request_id,receipt_number,issued_at']),
            $filters['sort'] ?? 'newest',
        );
    }

    /**
     * @param  array<string, string>  $filters
     */
    protected function cashierFilteredQuery(array $filters): Builder
    {
        return $this->cashierFilteredBaseQuery($filters)
            ->with(['receipt:id,payment_request_id,receipt_number'])
            ->orderByDesc('created_at');
    }

    /**
     * @param  array<string, string>  $filters
     */
    protected function filteredBaseQuery(array $filters): Builder
    {
        $search = trim($filters['search'] ?? '');

        $query = PaymentRequest::query();

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('matric_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhereHas('receipt', function (Builder $receiptQuery) use ($search): void {
                        $receiptQuery->where('receipt_number', 'like', "%{$search}%");
                    });
            });
        }

        $query
            ->when(($filters['payment_type_id'] ?? '') !== '', fn (Builder $query): Builder => $query->where('payment_type_id', $filters['payment_type_id']))
            ->when(($filters['payment_status'] ?? '') !== '', fn (Builder $query): Builder => $query->where('payment_status', $filters['payment_status']))
            ->when(($filters['department'] ?? '') !== '', fn (Builder $query): Builder => $query->where('department', $filters['department']))
            ->when(($filters['faculty'] ?? '') !== '', fn (Builder $query): Builder => $query->where('faculty', $filters['faculty']))
            ->when(($filters['graduation_session'] ?? '') !== '', fn (Builder $query): Builder => $query->where('graduation_session', $filters['graduation_session']))
            ->when(($filters['date_from'] ?? '') !== '', fn (Builder $query): Builder => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(($filters['date_to'] ?? '') !== '', fn (Builder $query): Builder => $query->whereDate('created_at', '<=', $filters['date_to']));

        return $query;
    }

    /**
     * @param  array<string, string>  $filters
     */
    protected function cashierFilteredBaseQuery(array $filters): Builder
    {
        $search = trim($filters['search'] ?? '');
        $query = PaymentRequest::query();

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('matric_number', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhere('paystack_reference', 'like', "%{$search}%")
                    ->orWhereHas('receipt', function (Builder $receiptQuery) use ($search): void {
                        $receiptQuery->where('receipt_number', 'like', "%{$search}%");
                    });
            });
        }

        $query->when(
            ($filters['status'] ?? '') !== '',
            fn (Builder $query): Builder => $query->where('payment_status', $filters['status']),
        );

        $query
            ->when(($filters['date_from'] ?? '') !== '', fn (Builder $query): Builder => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(($filters['date_to'] ?? '') !== '', fn (Builder $query): Builder => $query->whereDate('created_at', '<=', $filters['date_to']));

        return $query;
    }

    protected function applySort(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'oldest' => $query->orderBy('created_at'),
            'amount_asc' => $query->orderBy('amount')->orderByDesc('created_at'),
            'amount_desc' => $query->orderByDesc('amount')->orderByDesc('created_at'),
            'name_asc' => $query->orderBy('full_name')->orderByDesc('created_at'),
            'name_desc' => $query->orderByDesc('full_name')->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };
    }
}
