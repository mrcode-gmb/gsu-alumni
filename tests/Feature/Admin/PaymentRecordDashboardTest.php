<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentRequestStatus;
use App\Models\PaymentRequest;
use App\Models\PaymentType;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentRecordDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_alumni_admin_can_view_admin_dashboard_and_payment_record_pages()
    {
        $admin = User::factory()->alumniAdmin()->create();
        $paymentType = PaymentType::factory()->create([
            'name' => 'Certificate Registration',
            'amount' => 12000,
        ]);

        $successfulRequest = PaymentRequest::factory()->create([
            'full_name' => 'Amina Musa',
            'matric_number' => 'GSU/19/4455',
            'payment_type_id' => $paymentType->id,
            'payment_type_name' => $paymentType->name,
            'payment_type_description' => $paymentType->description,
            'amount' => '12000.00',
            'payment_status' => PaymentRequestStatus::Successful,
            'payment_reference' => 'GSUAPR-REC-100',
            'paystack_reference' => 'GSUAPR-REC-100',
            'payment_channel' => 'card',
            'paid_at' => now(),
        ]);

        $this->createReceiptFor($successfulRequest, 'GSU-RCP-20260328-ADM001');

        $dashboardResponse = $this->actingAs($admin)->get(route('dashboard'));

        $dashboardResponse
            ->assertOk()
            ->assertSee('Admin dashboard')
            ->assertSee('Payment records oversight');

        $recordsResponse = $this->actingAs($admin)->get(route('admin.payment-records.index'));

        $recordsResponse
            ->assertOk()
            ->assertSee('Admin payment records')
            ->assertSee('Amina Musa')
            ->assertSee('Certificate Registration');

        $detailResponse = $this->actingAs($admin)->get(route('admin.payment-records.show', $successfulRequest));

        $detailResponse
            ->assertOk()
            ->assertSee('Payment record details')
            ->assertSee('GSU/19/4455')
            ->assertSee('GSUAPR-REC-100');

        $printResponse = $this->actingAs($admin)->get(route('admin.payment-records.print-single', $successfulRequest));

        $printResponse
            ->assertOk()
            ->assertSee('Print payment record');
    }

    public function test_students_cannot_access_admin_payment_records_module()
    {
        $student = User::factory()->create();

        $response = $this->actingAs($student)->get(route('admin.payment-records.index'));

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    public function test_admin_can_search_payment_records_by_receipt_number()
    {
        $admin = User::factory()->alumniAdmin()->create();
        $paymentType = PaymentType::factory()->create([
            'name' => 'Alumni Registration',
        ]);

        $matchingRequest = PaymentRequest::factory()->create([
            'full_name' => 'Fatima Bello',
            'matric_number' => 'GSU/18/0101',
            'payment_type_id' => $paymentType->id,
            'payment_type_name' => $paymentType->name,
            'payment_status' => PaymentRequestStatus::Successful,
            'payment_reference' => 'GSUAPR-FATIMA',
            'paystack_reference' => 'GSUAPR-FATIMA',
            'paid_at' => now(),
        ]);

        $otherRequest = PaymentRequest::factory()->create([
            'full_name' => 'Sani Ibrahim',
            'matric_number' => 'GSU/18/0102',
            'payment_type_id' => $paymentType->id,
            'payment_type_name' => $paymentType->name,
            'payment_status' => PaymentRequestStatus::Pending,
        ]);

        $this->createReceiptFor($matchingRequest, 'GSU-RCP-20260328-FAT001');

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.payment-records.index', [
                'search' => 'GSU-RCP-20260328-FAT001',
            ]));

        $response
            ->assertOk()
            ->assertSee('Fatima Bello')
            ->assertDontSee('Sani Ibrahim');

        $this->assertDatabaseHas('payment_requests', [
            'id' => $otherRequest->id,
        ]);
    }

    public function test_admin_receipt_action_reuses_or_creates_receipt_for_successful_payment()
    {
        $admin = User::factory()->alumniAdmin()->create();
        $paymentRequest = PaymentRequest::factory()->create([
            'payment_status' => PaymentRequestStatus::Successful,
            'payment_reference' => 'GSUAPR-REC-222',
            'paystack_reference' => 'GSUAPR-REC-222',
            'payment_channel' => 'card',
            'paid_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.payment-records.show', $paymentRequest))
            ->post(route('admin.payment-records.receipt', $paymentRequest));

        $receipt = Receipt::query()->firstOrFail();

        $response->assertRedirect();
        $this->assertStringContainsString('/receipts/', (string) $response->headers->get('Location'));
        $this->assertSame($paymentRequest->id, $receipt->payment_request_id);
        $this->assertDatabaseCount('receipts', 1);
    }

    public function test_admin_receipt_action_is_blocked_for_pending_payment()
    {
        $admin = User::factory()->alumniAdmin()->create();
        $paymentRequest = PaymentRequest::factory()->create([
            'payment_status' => PaymentRequestStatus::Pending,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.payment-records.show', $paymentRequest))
            ->post(route('admin.payment-records.receipt', $paymentRequest));

        $response
            ->assertRedirect(route('admin.payment-records.show', $paymentRequest))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('receipts', 0);
    }

    public function test_admin_can_open_filtered_print_view()
    {
        $admin = User::factory()->alumniAdmin()->create();
        $paymentType = PaymentType::factory()->create([
            'name' => 'ID Card Fee',
        ]);

        PaymentRequest::factory()->create([
            'full_name' => 'Maryam Goni',
            'faculty' => 'Science',
            'payment_type_id' => $paymentType->id,
            'payment_type_name' => $paymentType->name,
            'payment_status' => PaymentRequestStatus::Successful,
            'paid_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.payment-records.print', [
                'faculty' => 'Science',
                'sort' => 'newest',
            ]));

        $response
            ->assertOk()
            ->assertSee('Print filtered payment records')
            ->assertSee('Maryam Goni');
    }

    protected function createReceiptFor(PaymentRequest $paymentRequest, string $receiptNumber): Receipt
    {
        return Receipt::query()->create([
            'payment_request_id' => $paymentRequest->id,
            'public_reference' => (string) Str::ulid(),
            'receipt_number' => $receiptNumber,
            'issued_at' => now(),
            'official_note' => 'This is evidence of payment.',
            'snapshot' => [
                'payment_request_public_reference' => $paymentRequest->public_reference,
                'payment_date' => $paymentRequest->paid_at?->toIso8601String(),
                'full_name' => $paymentRequest->full_name,
                'matric_number' => $paymentRequest->matric_number,
                'email' => $paymentRequest->email,
                'phone_number' => $paymentRequest->phone_number,
                'department' => $paymentRequest->department,
                'faculty' => $paymentRequest->faculty,
                'graduation_session' => $paymentRequest->graduation_session,
                'payment_type_name' => $paymentRequest->payment_type_name,
                'amount' => (string) $paymentRequest->amount,
                'payment_status' => $paymentRequest->payment_status->value,
                'payment_status_label' => $paymentRequest->payment_status->label(),
                'payment_reference' => $paymentRequest->payment_reference,
                'paystack_reference' => $paymentRequest->paystack_reference,
                'payment_channel' => $paymentRequest->payment_channel,
                'transaction_reference' => $paymentRequest->transaction_reference,
            ],
        ]);
    }
}
