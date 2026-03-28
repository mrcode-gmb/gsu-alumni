<?php

namespace Database\Factories;

use App\Enums\PaymentRequestStatus;
use App\Models\PaymentRequest;
use App\Models\Receipt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    protected $model = Receipt::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paymentRequest = PaymentRequest::factory()->create([
            'payment_status' => PaymentRequestStatus::Successful,
            'payment_reference' => 'GSUAPR-'.Str::upper(Str::random(12)),
            'paystack_reference' => 'GSUAPR-'.Str::upper(Str::random(12)),
            'payment_channel' => fake()->randomElement(['card', 'bank_transfer']),
            'paid_at' => now()->subMinutes(fake()->numberBetween(5, 90)),
        ]);

        return [
            'payment_request_id' => $paymentRequest->id,
            'public_reference' => (string) Str::ulid(),
            'receipt_number' => 'GSU-RCP-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
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
                'amount' => $paymentRequest->amount,
                'payment_status' => PaymentRequestStatus::Successful->value,
                'payment_status_label' => PaymentRequestStatus::Successful->label(),
                'payment_reference' => $paymentRequest->payment_reference,
                'paystack_reference' => $paymentRequest->paystack_reference,
                'payment_channel' => $paymentRequest->payment_channel,
                'transaction_reference' => $paymentRequest->transaction_reference,
            ],
        ];
    }
}
