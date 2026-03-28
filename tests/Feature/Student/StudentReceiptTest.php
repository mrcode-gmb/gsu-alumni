<?php

namespace Tests\Feature\Student;

use App\Enums\PaymentRequestStatus;
use App\Models\PaymentRequest;
use App\Models\Receipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class StudentReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_payment_request_can_generate_and_reuse_one_receipt()
    {
        $paymentRequest = PaymentRequest::factory()->create([
            'payment_status' => PaymentRequestStatus::Successful,
            'payment_reference' => 'GSUAPR-REC-001',
            'paystack_reference' => 'GSUAPR-REC-001',
            'payment_channel' => 'card',
            'paid_at' => now(),
        ]);

        $firstResponse = $this->post(route('student-receipts.from-payment-request', $paymentRequest));

        $receipt = Receipt::query()->firstOrFail();

        $firstResponse->assertRedirect(URL::signedRoute('student-receipts.show', [
            'receipt' => $receipt,
        ]));

        $secondResponse = $this->post(route('student-receipts.from-payment-request', $paymentRequest));

        $secondResponse->assertRedirect(URL::signedRoute('student-receipts.show', [
            'receipt' => $receipt->fresh(),
        ]));

        $this->assertDatabaseCount('receipts', 1);
    }

    public function test_pending_payment_request_cannot_generate_receipt()
    {
        $paymentRequest = PaymentRequest::factory()->create([
            'payment_status' => PaymentRequestStatus::Pending,
        ]);

        $response = $this
            ->from(route('student-payments.show', $paymentRequest))
            ->post(route('student-receipts.from-payment-request', $paymentRequest));

        $response
            ->assertRedirect(route('student-payments.show', $paymentRequest))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('receipts', 0);
    }

    public function test_receipt_lookup_redirects_to_signed_receipt_page()
    {
        $receipt = Receipt::factory()->create([
            'receipt_number' => 'GSU-RCP-20260327-ABC123',
            'snapshot' => [
                'payment_request_public_reference' => '01JQ8P4FW2P1KR2Y4V1R77M6MZ',
                'payment_date' => now()->toIso8601String(),
                'full_name' => 'Amina Umar',
                'matric_number' => 'GSU/19/0099',
                'email' => 'amina@example.com',
                'phone_number' => '08011112222',
                'department' => 'Microbiology',
                'faculty' => 'Science',
                'graduation_session' => '2023/2024',
                'payment_type_name' => 'Certificate Registration',
                'amount' => '12000.00',
                'payment_status' => PaymentRequestStatus::Successful->value,
                'payment_status_label' => PaymentRequestStatus::Successful->label(),
                'payment_reference' => 'GSUAPR-REC-0099',
                'paystack_reference' => 'GSUAPR-REC-0099',
                'payment_channel' => 'card',
                'transaction_reference' => '998877',
            ],
        ]);

        $receipt->paymentRequest->forceFill([
            'matric_number' => 'GSU/19/0099',
            'payment_status' => PaymentRequestStatus::Successful,
        ])->save();

        $response = $this->post(route('student-receipts.search'), [
            'receipt_number' => 'gsu-rcp-20260327-abc123',
            'matric_number' => 'gsu/19/0099',
        ]);

        $response->assertRedirect(URL::signedRoute('student-receipts.show', [
            'receipt' => $receipt,
        ]));
    }

    public function test_receipt_lookup_rejects_wrong_matric_number()
    {
        $receipt = Receipt::factory()->create([
            'receipt_number' => 'GSU-RCP-20260327-WRONG1',
        ]);

        $receipt->paymentRequest->forceFill([
            'matric_number' => 'GSU/17/1000',
            'payment_status' => PaymentRequestStatus::Successful,
        ])->save();

        $response = $this
            ->from(route('student-receipts.lookup'))
            ->post(route('student-receipts.search'), [
                'receipt_number' => 'GSU-RCP-20260327-WRONG1',
                'matric_number' => 'GSU/17/0000',
            ]);

        $response
            ->assertRedirect(route('student-receipts.lookup'))
            ->assertSessionHasErrors('receipt_number');
    }

    public function test_receipt_show_requires_a_valid_signature()
    {
        $receipt = Receipt::factory()->create();

        $response = $this->get(route('student-receipts.show', [
            'receipt' => $receipt,
        ], false));

        $response->assertForbidden();
    }
}
