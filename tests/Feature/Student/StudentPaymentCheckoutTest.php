<?php

namespace Tests\Feature\Student;

use App\Enums\PaymentRequestStatus;
use App\Models\PaymentRequest;
use App\Models\PaymentType;
use App\Models\Receipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class StudentPaymentCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.paystack.secret_key', 'sk_test_example');
        config()->set('services.paystack.public_key', 'pk_test_example');
        config()->set('services.paystack.base_url', 'https://api.paystack.co');
        config()->set('services.paystack.currency', 'NGN');
        config()->set('services.paystack.callback_url', 'https://portal.test/payments/paystack/callback');
        config()->set('services.paystack.webhook_secret', 'sk_test_example');
    }

    public function test_pending_payment_request_can_be_initialized_with_paystack()
    {
        $paymentType = PaymentType::factory()->create([
            'amount' => 7500,
            'is_active' => true,
        ]);

        $paymentRequest = PaymentRequest::factory()->create([
            'payment_type_id' => $paymentType->id,
            'payment_type_name' => $paymentType->name,
            'payment_type_description' => $paymentType->description,
            'amount' => '7500.00',
            'payment_status' => PaymentRequestStatus::Pending,
            'payment_reference' => null,
            'paystack_reference' => null,
        ]);

        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'message' => 'Authorization URL created',
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/test-token',
                    'access_code' => 'ACCESS_TEST_CODE',
                    'reference' => 'PSK_TEST_REFERENCE',
                ],
            ], 200),
        ]);

        $response = $this->post(route('student-payments.paystack.initialize', $paymentRequest));

        $response->assertRedirect('https://checkout.paystack.com/test-token');

        $paymentRequest->refresh();

        $this->assertNotNull($paymentRequest->payment_reference);
        $this->assertNotNull($paymentRequest->paystack_reference);
        $this->assertSame('ACCESS_TEST_CODE', $paymentRequest->transaction_reference);
        $this->assertSame(PaymentRequestStatus::Pending, $paymentRequest->payment_status);

        Http::assertSent(function ($request) use ($paymentRequest) {
            return $request->url() === 'https://api.paystack.co/transaction/initialize'
                && $request['email'] === $paymentRequest->email
                && $request['amount'] === 750000
                && $request['reference'] === $paymentRequest->paystack_reference
                && $request['currency'] === 'NGN'
                && data_get($request['metadata'], 'cancel_action') === route('student-payments.paystack.cancel', $paymentRequest, absolute: true);
        });
    }

    public function test_pending_payment_request_can_prepare_popup_checkout_without_calling_paystack_api()
    {
        $paymentType = PaymentType::factory()->create([
            'amount' => 7500,
            'is_active' => true,
        ]);

        $paymentRequest = PaymentRequest::factory()->create([
            'payment_type_id' => $paymentType->id,
            'payment_type_name' => $paymentType->name,
            'payment_type_description' => $paymentType->description,
            'amount' => '7500.00',
            'payment_status' => PaymentRequestStatus::Pending,
            'payment_reference' => null,
            'paystack_reference' => null,
        ]);

        Http::fake();

        $response = $this->postJson(route('student-payments.paystack.initialize', $paymentRequest));

        $response
            ->assertOk()
            ->assertJsonPath('checkout.key', 'pk_test_example')
            ->assertJsonPath('checkout.email', $paymentRequest->email)
            ->assertJsonPath('checkout.amount', 750000)
            ->assertJsonPath('checkout.currency', 'NGN');

        $paymentRequest->refresh();

        $this->assertNotNull($paymentRequest->payment_reference);
        $this->assertNotNull($paymentRequest->paystack_reference);
        Http::assertNothingSent();
    }

    public function test_successful_payment_request_cannot_be_reinitialized()
    {
        $paymentRequest = PaymentRequest::factory()->create([
            'payment_status' => PaymentRequestStatus::Successful,
        ]);

        Http::fake();

        $response = $this
            ->from(route('student-payments.show', $paymentRequest))
            ->post(route('student-payments.paystack.initialize', $paymentRequest));

        $response
            ->assertRedirect(route('student-payments.show', $paymentRequest))
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }

    public function test_callback_verifies_successful_payment_and_updates_request()
    {
        $paymentType = PaymentType::factory()->create([
            'amount' => 10000,
        ]);

        $paymentRequest = PaymentRequest::factory()->create([
            'payment_type_id' => $paymentType->id,
            'payment_type_name' => $paymentType->name,
            'payment_type_description' => $paymentType->description,
            'amount' => '10000.00',
            'email' => 'student@example.com',
            'payment_reference' => 'GSUAPR-TEST-001',
            'paystack_reference' => 'GSUAPR-TEST-001',
            'payment_status' => PaymentRequestStatus::Pending,
        ]);

        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'id' => 9123456,
                    'status' => 'success',
                    'reference' => 'GSUAPR-TEST-001',
                    'amount' => 1000000,
                    'currency' => 'NGN',
                    'channel' => 'card',
                    'gateway_response' => 'Successful',
                    'paid_at' => '2026-03-27T12:00:00.000Z',
                    'customer' => [
                        'email' => 'student@example.com',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->get(route('student-payments.paystack.callback', [
            'reference' => 'GSUAPR-TEST-001',
        ]));

        $receipt = Receipt::query()->first();

        $response->assertRedirect(URL::signedRoute('student-receipts.show', [
            'receipt' => $receipt,
        ]));

        $paymentRequest->refresh();

        $this->assertSame(PaymentRequestStatus::Successful, $paymentRequest->payment_status);
        $this->assertSame('card', $paymentRequest->payment_channel);
        $this->assertSame('9123456', $paymentRequest->transaction_reference);
        $this->assertSame('Successful', $paymentRequest->gateway_response);
        $this->assertNotNull($paymentRequest->paid_at);
        $this->assertIsArray($paymentRequest->verification_payload);
        $this->assertSame($paymentRequest->id, $receipt->payment_request_id);
    }

    public function test_callback_marks_abandoned_payment_when_paystack_reports_abandoned()
    {
        $paymentRequest = PaymentRequest::factory()->create([
            'amount' => '4000.00',
            'email' => 'student@example.com',
            'payment_reference' => 'GSUAPR-TEST-002',
            'paystack_reference' => 'GSUAPR-TEST-002',
            'payment_status' => PaymentRequestStatus::Pending,
        ]);

        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'id' => 445566,
                    'status' => 'abandoned',
                    'reference' => 'GSUAPR-TEST-002',
                    'amount' => 400000,
                    'currency' => 'NGN',
                    'gateway_response' => 'Transaction abandoned',
                    'customer' => [
                        'email' => 'student@example.com',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->get(route('student-payments.paystack.callback', [
            'reference' => 'GSUAPR-TEST-002',
        ]));

        $response->assertRedirect(route('student-payments.show', $paymentRequest));
        $this->assertSame(PaymentRequestStatus::Abandoned, $paymentRequest->fresh()->payment_status);
    }

    public function test_duplicate_callback_for_successful_payment_does_not_call_paystack_again()
    {
        $paymentRequest = PaymentRequest::factory()->create([
            'payment_reference' => 'GSUAPR-TEST-003',
            'paystack_reference' => 'GSUAPR-TEST-003',
            'payment_status' => PaymentRequestStatus::Successful,
        ]);

        Http::fake();

        $response = $this->get(route('student-payments.paystack.callback', [
            'reference' => 'GSUAPR-TEST-003',
        ]));

        $receipt = Receipt::query()->first();

        $response->assertRedirect(URL::signedRoute('student-receipts.show', [
            'receipt' => $receipt,
        ]));
        Http::assertNothingSent();
        $this->assertSame($paymentRequest->id, $receipt->payment_request_id);
    }

    public function test_amount_mismatch_does_not_mark_payment_request_as_successful()
    {
        $paymentRequest = PaymentRequest::factory()->create([
            'amount' => '6500.00',
            'email' => 'student@example.com',
            'payment_reference' => 'GSUAPR-TEST-004',
            'paystack_reference' => 'GSUAPR-TEST-004',
            'payment_status' => PaymentRequestStatus::Pending,
        ]);

        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'id' => 778899,
                    'status' => 'success',
                    'reference' => 'GSUAPR-TEST-004',
                    'amount' => 100000,
                    'currency' => 'NGN',
                    'gateway_response' => 'Successful',
                    'customer' => [
                        'email' => 'student@example.com',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->get(route('student-payments.paystack.callback', [
            'reference' => 'GSUAPR-TEST-004',
        ]));

        $response->assertRedirect(route('student-payments.create'));
        $this->assertSame(PaymentRequestStatus::Pending, $paymentRequest->fresh()->payment_status);
    }

    public function test_valid_paystack_webhook_verifies_successful_payment_and_issues_receipt()
    {
        $paymentRequest = PaymentRequest::factory()->create([
            'amount' => '5500.00',
            'email' => 'student@example.com',
            'payment_reference' => 'GSUAPR-WEBHOOK-001',
            'paystack_reference' => 'GSUAPR-WEBHOOK-001',
            'payment_status' => PaymentRequestStatus::Pending,
        ]);

        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'id' => 665544,
                    'status' => 'success',
                    'reference' => 'GSUAPR-WEBHOOK-001',
                    'amount' => 550000,
                    'currency' => 'NGN',
                    'channel' => 'bank_transfer',
                    'gateway_response' => 'Successful',
                    'paid_at' => '2026-03-28T12:00:00.000Z',
                    'customer' => [
                        'email' => 'student@example.com',
                    ],
                ],
            ], 200),
        ]);

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'GSUAPR-WEBHOOK-001',
            ],
        ];

        $signature = hash_hmac('sha512', json_encode($payload, JSON_THROW_ON_ERROR), 'sk_test_example');

        $response = $this
            ->withHeader('x-paystack-signature', $signature)
            ->postJson(route('student-payments.paystack.webhook'), $payload);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Webhook processed successfully.',
            ]);

        $paymentRequest->refresh();

        $this->assertSame(PaymentRequestStatus::Successful, $paymentRequest->payment_status);
        $this->assertDatabaseHas('receipts', [
            'payment_request_id' => $paymentRequest->id,
        ]);
    }

    public function test_invalid_paystack_webhook_signature_is_rejected()
    {
        Http::fake();

        $response = $this
            ->withHeader('x-paystack-signature', 'invalid-signature')
            ->postJson(route('student-payments.paystack.webhook'), [
                'event' => 'charge.success',
                'data' => [
                    'reference' => 'UNKNOWN-REFERENCE',
                ],
            ]);

        $response->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_cancel_route_marks_pending_payment_request_as_abandoned()
    {
        $paymentRequest = PaymentRequest::factory()->create([
            'payment_status' => PaymentRequestStatus::Pending,
        ]);

        $response = $this->get(route('student-payments.paystack.cancel', $paymentRequest));

        $response->assertRedirect(route('student-payments.show', $paymentRequest));
        $this->assertSame(PaymentRequestStatus::Abandoned, $paymentRequest->fresh()->payment_status);
    }
}
