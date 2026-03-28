<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentTypes\StorePaymentTypeRequest;
use App\Http\Requests\Admin\PaymentTypes\UpdatePaymentTypeRequest;
use App\Http\Requests\Admin\PaymentTypes\UpdatePaymentTypeStatusRequest;
use App\Models\PaymentType;
use App\Models\ProgramType;
use App\Services\PaymentTypeService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentTypeController extends Controller
{
    public function __construct(
        protected PaymentTypeService $paymentTypeService,
    ) {
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));

        $paymentTypes = PaymentType::query()
            ->with(['programTypes' => fn ($query) => $query->ordered()->select('program_types.id', 'name')])
            ->search($search)
            ->ordered()
            ->get();

        $usedPaymentTypeIds = $this->paymentTypeService->usedPaymentTypeIds(
            $paymentTypes->pluck('id')->all(),
        );

        return Inertia::render('admin/payment-types/index', [
            'paymentTypes' => $paymentTypes->map(
                fn (PaymentType $paymentType): array => $this->paymentTypePayload(
                    $paymentType,
                    ! in_array($paymentType->id, $usedPaymentTypeIds, true),
                ),
            ),
            'filters' => [
                'search' => $search,
            ],
            'summary' => [
                'total' => PaymentType::count(),
                'active' => PaymentType::where('is_active', true)->count(),
                'inactive' => PaymentType::where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/payment-types/create', [
            'programTypeOptions' => $this->programTypeOptions(),
        ]);
    }

    public function store(StorePaymentTypeRequest $request): RedirectResponse
    {
        $this->paymentTypeService->create($request->validated());

        return to_route('admin.payment-types.index')
            ->with('success', 'Payment type created successfully.');
    }

    public function edit(PaymentType $paymentType): Response
    {
        $paymentType->loadMissing(['programTypes' => fn ($query) => $query->ordered()->select('program_types.id', 'name')]);

        return Inertia::render('admin/payment-types/edit', [
            'paymentType' => $this->paymentTypePayload(
                $paymentType,
                ! $this->paymentTypeService->hasRecordedPayments($paymentType),
            ),
            'programTypeOptions' => $this->programTypeOptions(),
        ]);
    }

    public function update(UpdatePaymentTypeRequest $request, PaymentType $paymentType): RedirectResponse
    {
        $this->paymentTypeService->update($paymentType, $request->validated());

        return to_route('admin.payment-types.index')
            ->with('success', 'Payment type updated successfully.');
    }

    public function updateStatus(
        UpdatePaymentTypeStatusRequest $request,
        PaymentType $paymentType,
    ): RedirectResponse {
        $isActive = (bool) $request->validated('is_active');

        $this->paymentTypeService->updateStatus($paymentType, $isActive);

        return back()->with(
            'success',
            $isActive
                ? 'Payment type activated successfully.'
                : 'Payment type deactivated successfully.',
        );
    }

    public function destroy(PaymentType $paymentType): RedirectResponse
    {
        try {
            $this->paymentTypeService->delete($paymentType);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Payment type deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function paymentTypePayload(PaymentType $paymentType, bool $canDelete): array
    {
        return [
            'id' => $paymentType->id,
            'name' => $paymentType->name,
            'amount' => $paymentType->amount,
            'description' => $paymentType->description,
            'program_type_ids' => $paymentType->programTypes
                ->pluck('id')
                ->map(fn (mixed $programTypeId): string => (string) $programTypeId)
                ->values()
                ->all(),
            'program_types' => $paymentType->programTypes
                ->pluck('name')
                ->values()
                ->all(),
            'is_active' => $paymentType->is_active,
            'display_order' => $paymentType->display_order,
            'can_delete' => $canDelete,
            'created_at' => $paymentType->created_at?->toIso8601String(),
            'updated_at' => $paymentType->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function programTypeOptions(): array
    {
        return ProgramType::query()
            ->ordered()
            ->get(['id', 'name', 'is_active'])
            ->map(fn (ProgramType $programType): array => [
                'value' => (string) $programType->id,
                'label' => $programType->is_active
                    ? $programType->name
                    : $programType->name.' (Inactive)',
            ])
            ->values()
            ->all();
    }
}
