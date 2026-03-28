<?php

namespace Database\Factories;

use App\Enums\PaymentRequestStatus;
use App\Models\PaymentRequest;
use App\Models\PaymentType;
use App\Models\ProgramType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentRequest>
 */
class PaymentRequestFactory extends Factory
{
    protected $model = PaymentRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paymentType = PaymentType::factory()->create();
        $programType = ProgramType::factory()->create();
        $programme = fake()->randomElement([
            ['faculty' => 'Faculty of Sciences', 'department' => 'Computer Science'],
            ['faculty' => 'Faculty of Sciences', 'department' => 'Biochemistry'],
            ['faculty' => 'Faculty of Arts and Social Sciences', 'department' => 'Accounting'],
            ['faculty' => 'Faculty of Arts and Social Sciences', 'department' => 'Economics'],
            ['faculty' => 'Faculty of Environmental Sciences', 'department' => 'Estate Management'],
            ['faculty' => 'Faculty of Education', 'department' => 'Science Education'],
        ]);

        return [
            'public_reference' => (string) Str::ulid(),
            'full_name' => fake()->name(),
            'matric_number' => strtoupper(fake()->bothify('GSU/##/###')),
            'email' => fake()->safeEmail(),
            'phone_number' => fake()->numerify('080########'),
            'department' => $programme['department'],
            'faculty' => $programme['faculty'],
            'program_type_id' => $programType->id,
            'program_type_name' => $programType->name,
            'graduation_session' => fake()->randomElement(['2023', '2023/2024', '2024']),
            'payment_type_id' => $paymentType->id,
            'payment_type_name' => $paymentType->name,
            'payment_type_description' => $paymentType->description,
            'amount' => $paymentType->amount,
            'payment_status' => PaymentRequestStatus::Pending,
            'payment_reference' => null,
            'paystack_reference' => null,
            'paid_at' => null,
            'payment_channel' => null,
            'gateway_response' => null,
            'initialization_payload' => null,
            'verification_payload' => null,
            'transaction_reference' => null,
        ];
    }
}
