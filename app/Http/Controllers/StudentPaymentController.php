<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentPayments\AccessPaymentRequest;
use App\Http\Requests\StudentPayments\StoreStudentPaymentRequest;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\PaymentRequest;
use App\Models\PaymentType;
use App\Models\ProgramType;
use App\Services\PaymentTypeChargeService;
use App\Services\PaymentRequestService;
use App\Services\PaymentCheckoutService;
use App\Support\GraduationSessionOptions;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class StudentPaymentController extends Controller
{
    public function __construct(
        protected PaymentRequestService $paymentRequestService,
        protected PaymentTypeChargeService $paymentTypeChargeService,
        protected PaymentCheckoutService $paymentCheckoutService,
    ) {
    }

    public function create(): Response
    {
        return Inertia::render('student-payments/create', [
            'faculties' => Faculty::query()
                ->active()
                ->ordered()
                ->get(['name'])
                ->map(fn (Faculty $faculty): array => [
                    'value' => $faculty->name,
                    'label' => $faculty->name,
                ])
                ->values(),
            'departments' => Department::query()
                ->with('faculty:id,name')
                ->active()
                ->whereHas('faculty', fn ($query) => $query->where('is_active', true))
                ->ordered()
                ->get()
                ->map(fn (Department $department): array => [
                    'value' => $department->name,
                    'label' => $department->name,
                    'faculty_name' => $department->faculty?->name,
                ])
                ->values(),
            'programTypes' => ProgramType::query()
                ->active()
                ->ordered()
                ->get(['id', 'name'])
                ->map(fn (ProgramType $programType): array => [
                    'value' => (string) $programType->id,
                    'label' => $programType->name,
                ])
                ->values(),
            'graduationSessions' => collect(GraduationSessionOptions::values())
                ->map(fn (string $session): array => [
                    'value' => $session,
                    'label' => $session,
                ])
                ->values(),
            'paymentTypes' => PaymentType::query()
                ->whereHas('programTypes', fn ($query) => $query->where('program_types.is_active', true))
                ->with(['programTypes:id'])
                ->active()
                ->ordered()
                ->get()
                ->map(function (PaymentType $paymentType): array {
                    $chargeBreakdown = $this->paymentTypeChargeService->resolveForPaymentType($paymentType);

                    return [
                        'id' => $paymentType->id,
                        'name' => $paymentType->name,
                        'amount' => $chargeBreakdown['total_amount'],
                        'base_amount' => $chargeBreakdown['base_amount'],
                        'portal_charge_amount' => $chargeBreakdown['service_charge_amount'],
                        'paystack_charge_amount' => $chargeBreakdown['paystack_charge_amount'],
                        'description' => $paymentType->description,
                        'program_type_ids' => $paymentType->programTypes
                            ->pluck('id')
                            ->map(fn (mixed $programTypeId): string => (string) $programTypeId)
                            ->values()
                            ->all(),
                    ];
                }),
        ]);
    }

    public function store(StoreStudentPaymentRequest $request): RedirectResponse
    {
        $result = $this->paymentRequestService->createOrReusePending($request->validated());
        $paymentRequest = $result['paymentRequest'];
        $reused = $result['reused'];
        $reusedInitialized = $result['reused_initialized'];

        try {
            $paymentRequest = $this->paymentCheckoutService->initializePayment($paymentRequest)['paymentRequest'];
        } catch (Throwable $exception) {
            $persistedPaymentRequest = PaymentRequest::query()->find($paymentRequest->getKey());

            if (
                $persistedPaymentRequest instanceof PaymentRequest
                && ! $persistedPaymentRequest->hasPaystackInitialization()
            ) {
                $persistedPaymentRequest->delete();

                Log::warning('Uninitialized payment request deleted after Paystack initialization failure.', [
                    'payment_request_id' => $paymentRequest->getKey(),
                    'message' => $exception->getMessage(),
                ]);
            }

            return back()->with('error', 'We could not initialize Paystack for this payment. Please try again.');
        }

        $request->session()->put($this->accessSessionKey($paymentRequest), true);

        return to_route('student-payments.show', $paymentRequest)
            ->with([
                'success' => $reusedInitialized
                    ? 'An existing Paystack-initialized request was found and reopened for payment.'
                    : ($reused
                        ? 'An existing pending request was found and updated for you.'
                        : 'Your payment request has been created and is ready for payment initialization.'),
                'auto_open_checkout' => true,
            ]);
    }

    public function show(Request $request, PaymentRequest $paymentRequest): Response
    {
        if (! $this->hasAccess($request, $paymentRequest)) {
            return Inertia::render('student-payments/access', [
                'publicReference' => $paymentRequest->public_reference,
            ]);
        }

        $previousSuccessfulPaymentsCount = PaymentRequest::query()
            ->where('matric_number', $paymentRequest->matric_number)
            ->where('payment_type_id', $paymentRequest->payment_type_id)
            ->where('payment_status', \App\Enums\PaymentRequestStatus::Successful)
            ->whereKeyNot($paymentRequest->getKey())
            ->count();

        return Inertia::render('student-payments/show', [
            'paymentRequest' => [
                'public_reference' => $paymentRequest->public_reference,
                'full_name' => $paymentRequest->full_name,
                'matric_number' => $paymentRequest->matric_number,
                'email' => $paymentRequest->email,
                'phone_number' => $paymentRequest->phone_number,
                'department' => $paymentRequest->department,
                'faculty' => $paymentRequest->faculty,
                'program_type_name' => $paymentRequest->program_type_name,
                'graduation_session' => $paymentRequest->graduation_session,
                'payment_type_id' => $paymentRequest->payment_type_id,
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
                'can_initialize_payment' => $paymentRequest->canInitializePayment(),
                'previous_successful_payments_count' => $previousSuccessfulPaymentsCount,
            ],
            'paymentGatewayReady' => filled(config('services.paystack.secret_key'))
                && filled(config('services.paystack.public_key')),
            'autoOpenCheckout' => (bool) $request->session()->get('auto_open_checkout', false),
        ]);
    }

    public function access(AccessPaymentRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $validated = $request->validated();

        $submittedMatric = strtoupper(trim($validated['matric_number']));
        $submittedEmail = strtolower(trim($validated['email']));

        $storedMatric = strtoupper(trim($paymentRequest->matric_number));
        $storedEmail = strtolower(trim($paymentRequest->email));

        if ($submittedMatric !== $storedMatric || $submittedEmail !== $storedEmail) {
            return back()->withErrors([
                'matric_number' => 'The details do not match this payment request.',
            ]);
        }

        $request->session()->put($this->accessSessionKey($paymentRequest), true);

        return to_route('student-payments.show', $paymentRequest);
    }

    protected function hasAccess(Request $request, PaymentRequest $paymentRequest): bool
    {
        return (bool) $request->session()->get($this->accessSessionKey($paymentRequest), false);
    }

    protected function accessSessionKey(PaymentRequest $paymentRequest): string
    {
        return 'payment_request_access_'.$paymentRequest->getKey();
    }
}
