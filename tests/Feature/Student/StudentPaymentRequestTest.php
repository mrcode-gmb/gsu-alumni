<?php

namespace Tests\Feature\Student;

use App\Enums\PaymentRequestStatus;
use App\Enums\ChargeCalculationMode;
use App\Models\ChargeSetting;
use App\Models\PaymentRequest;
use App\Models\PaymentType;
use App\Models\ProgramType;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\FacultySeeder;
use Database\Seeders\ProgramTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPaymentRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProgramTypeSeeder::class);
        $this->seed(FacultySeeder::class);
        $this->seed(DepartmentSeeder::class);
    }

    public function test_payment_form_displays_active_payment_and_program_type_options()
    {
        $undergraduate = ProgramType::query()->where('name', 'Undergraduate')->firstOrFail();
        $activePaymentType = PaymentType::factory()->create([
            'name' => 'Certificate Registration',
            'is_active' => true,
        ]);
        $activePaymentType->programTypes()->sync([$undergraduate->id]);

        $inactivePaymentType = PaymentType::factory()->create([
            'name' => 'Legacy Clearance Fee',
            'is_active' => false,
        ]);
        $inactivePaymentType->programTypes()->sync([$undergraduate->id]);

        $inactiveProgramType = ProgramType::factory()->create([
            'name' => 'Legacy Programme',
            'slug' => 'legacy-programme',
            'is_active' => false,
        ]);

        $response = $this->get(route('student-payments.create'));

        $response
            ->assertOk()
            ->assertSee('Undergraduate')
            ->assertSee('2004/2005')
            ->assertSee('Faculty of Arts and Social Sciences')
            ->assertSee('Faculty of Sciences')
            ->assertSee($activePaymentType->name)
            ->assertDontSee($inactivePaymentType->name)
            ->assertDontSee($inactiveProgramType->name);
    }

    public function test_student_can_create_a_pending_payment_request()
    {
        $programType = ProgramType::query()->where('name', 'Undergraduate')->firstOrFail();
        $paymentType = PaymentType::factory()->create([
            'name' => 'Alumni Registration',
            'amount' => 5000,
            'description' => 'Alumni onboarding payment.',
            'is_active' => true,
        ]);
        $paymentType->programTypes()->sync([$programType->id]);

        $response = $this->post(route('student-payments.store'), [
            'full_name' => 'Aisha Musa',
            'matric_number' => 'gsu/19/2345',
            'email' => 'AISHA@example.com',
            'phone_number' => '08012345678',
            'department' => 'Computer Science',
            'faculty' => 'Faculty of Sciences',
            'program_type_id' => $programType->id,
            'graduation_session' => '2023/2024',
            'payment_type_id' => $paymentType->id,
            'amount' => '1.00',
        ]);

        $paymentRequest = PaymentRequest::query()->firstOrFail();

        $response->assertRedirect(route('student-payments.show', $paymentRequest));

        $this->assertDatabaseHas('payment_requests', [
            'id' => $paymentRequest->id,
            'full_name' => 'Aisha Musa',
            'matric_number' => 'GSU/19/2345',
            'email' => 'aisha@example.com',
            'program_type_id' => $programType->id,
            'program_type_name' => 'Undergraduate',
            'payment_type_id' => $paymentType->id,
            'payment_type_name' => 'Alumni Registration',
            'base_amount' => '5000.00',
            'portal_charge_amount' => '100.00',
            'paystack_charge_amount' => '100.00',
            'amount' => '5200.00',
            'payment_status' => PaymentRequestStatus::Pending->value,
        ]);
    }

    public function test_student_payment_request_snapshots_portal_and_paystack_charges()
    {
        ChargeSetting::query()->update([
            'portal_charge_mode' => ChargeCalculationMode::Percentage,
            'portal_charge_value' => '2.50',
            'paystack_percentage_rate' => '1.5000',
            'paystack_flat_fee' => '100.00',
        ]);

        $programType = ProgramType::query()->where('name', 'Undergraduate')->firstOrFail();
        $paymentType = PaymentType::factory()->create([
            'name' => 'Certificate Registration',
            'amount' => 10000,
            'is_active' => true,
        ]);
        $paymentType->programTypes()->sync([$programType->id]);

        $response = $this->post(route('student-payments.store'), [
            'full_name' => 'Amina Buba',
            'matric_number' => 'GSU/20/3333',
            'email' => 'amina@example.com',
            'phone_number' => '08012345670',
            'department' => 'Computer Science',
            'faculty' => 'Faculty of Sciences',
            'program_type_id' => $programType->id,
            'graduation_session' => '2023/2024',
            'payment_type_id' => $paymentType->id,
        ]);

        $paymentRequest = PaymentRequest::query()->firstOrFail();

        $response->assertRedirect(route('student-payments.show', $paymentRequest));

        $this->assertSame('10000.00', $paymentRequest->base_amount);
        $this->assertSame('250.00', $paymentRequest->portal_charge_amount);
        $this->assertSame('258.00', $paymentRequest->paystack_charge_amount);
        $this->assertSame('10508.00', $paymentRequest->amount);
        $this->assertIsArray($paymentRequest->charge_settings_snapshot);
    }

    public function test_inactive_program_type_cannot_be_submitted()
    {
        $paymentType = PaymentType::factory()->create([
            'is_active' => true,
        ]);

        $programType = ProgramType::factory()->create([
            'name' => 'Retired Programme',
            'slug' => 'retired-programme',
            'is_active' => false,
        ]);
        $paymentType->programTypes()->sync([$programType->id]);

        $response = $this
            ->from(route('student-payments.create'))
            ->post(route('student-payments.store'), [
                'full_name' => 'Umar Ahmed',
                'matric_number' => 'GSU/18/1111',
                'email' => 'umar@example.com',
                'phone_number' => '08087654321',
                'department' => 'Accounting',
                'faculty' => 'Faculty of Arts and Social Sciences',
                'program_type_id' => $programType->id,
                'graduation_session' => '2024/2025',
                'payment_type_id' => $paymentType->id,
            ]);

        $response
            ->assertRedirect(route('student-payments.create'))
            ->assertSessionHasErrors('program_type_id');

        $this->assertDatabaseCount('payment_requests', 0);
    }

    public function test_inactive_payment_type_cannot_be_submitted()
    {
        $programType = ProgramType::query()->where('name', 'Undergraduate')->firstOrFail();
        $paymentType = PaymentType::factory()->create([
            'is_active' => false,
        ]);
        $paymentType->programTypes()->sync([$programType->id]);

        $response = $this
            ->from(route('student-payments.create'))
            ->post(route('student-payments.store'), [
                'full_name' => 'Umar Ahmed',
                'matric_number' => 'GSU/18/1111',
                'email' => 'umar@example.com',
                'phone_number' => '08087654321',
                'department' => 'Accounting',
                'faculty' => 'Faculty of Arts and Social Sciences',
                'program_type_id' => $programType->id,
                'graduation_session' => '2024/2025',
                'payment_type_id' => $paymentType->id,
            ]);

        $response
            ->assertRedirect(route('student-payments.create'))
            ->assertSessionHasErrors('payment_type_id');

        $this->assertDatabaseCount('payment_requests', 0);
    }

    public function test_duplicate_pending_request_is_reused_instead_of_creating_another()
    {
        $programType = ProgramType::query()->where('name', 'Undergraduate')->firstOrFail();
        $paymentType = PaymentType::factory()->create([
            'name' => 'Certificate Registration',
            'amount' => 12000,
            'description' => 'Certificate processing payment.',
            'is_active' => true,
        ]);
        $paymentType->programTypes()->sync([$programType->id]);

        $existingRequest = PaymentRequest::factory()->create([
            'matric_number' => 'GSU/20/0001',
            'program_type_id' => $programType->id,
            'program_type_name' => $programType->name,
            'payment_type_id' => $paymentType->id,
            'payment_type_name' => $paymentType->name,
            'payment_type_description' => $paymentType->description,
            'amount' => $paymentType->amount,
            'payment_status' => PaymentRequestStatus::Pending,
            'phone_number' => '08000000000',
        ]);

        $response = $this->post(route('student-payments.store'), [
            'full_name' => 'Musa Aliyu',
            'matric_number' => 'gsu/20/0001',
            'email' => 'musa@example.com',
            'phone_number' => '08099999999',
            'department' => 'Biochemistry',
            'faculty' => 'Faculty of Sciences',
            'program_type_id' => $programType->id,
            'graduation_session' => '2022/2023',
            'payment_type_id' => $paymentType->id,
        ]);

        $response->assertRedirect(route('student-payments.show', $existingRequest));
        $this->assertDatabaseCount('payment_requests', 1);

        $existingRequest->refresh();

        $this->assertSame('Musa Aliyu', $existingRequest->full_name);
        $this->assertSame('08099999999', $existingRequest->phone_number);
        $this->assertSame('musa@example.com', $existingRequest->email);
        $this->assertSame('Undergraduate', $existingRequest->program_type_name);
        $this->assertSame('12000.00', $existingRequest->base_amount);
        $this->assertSame('100.00', $existingRequest->portal_charge_amount);
        $this->assertSame('100.00', $existingRequest->paystack_charge_amount);
        $this->assertSame('12200.00', $existingRequest->amount);
    }

    public function test_student_can_create_another_request_after_a_previous_successful_payment_for_the_same_type()
    {
        $programType = ProgramType::query()->where('name', 'Undergraduate')->firstOrFail();
        $paymentType = PaymentType::factory()->create([
            'name' => 'Certificate Registration',
            'amount' => 9000,
            'is_active' => true,
        ]);
        $paymentType->programTypes()->sync([$programType->id]);

        PaymentRequest::factory()->create([
            'matric_number' => 'GSU/16/9999',
            'program_type_id' => $programType->id,
            'program_type_name' => $programType->name,
            'payment_type_id' => $paymentType->id,
            'payment_type_name' => $paymentType->name,
            'payment_type_description' => $paymentType->description,
            'amount' => $paymentType->amount,
            'payment_status' => PaymentRequestStatus::Successful,
        ]);

        $response = $this->post(route('student-payments.store'), [
            'full_name' => 'Jamila Yusuf',
            'matric_number' => 'GSU/16/9999',
            'email' => 'jamila@example.com',
            'phone_number' => '08070000000',
            'department' => 'Economics',
            'faculty' => 'Faculty of Arts and Social Sciences',
            'program_type_id' => $programType->id,
            'graduation_session' => '2021/2022',
            'payment_type_id' => $paymentType->id,
        ]);

        $newRequest = PaymentRequest::query()
            ->where('payment_status', PaymentRequestStatus::Pending)
            ->latest('id')
            ->firstOrFail();

        $response->assertRedirect(route('student-payments.show', $newRequest));
        $this->assertDatabaseCount('payment_requests', 2);
        $this->assertSame('GSU/16/9999', $newRequest->matric_number);
        $this->assertSame($paymentType->id, $newRequest->payment_type_id);
        $this->assertSame(PaymentRequestStatus::Pending, $newRequest->payment_status);
    }

    public function test_student_can_revisit_saved_review_page()
    {
        $paymentType = PaymentType::factory()->create([
            'name' => 'ID Card Fee',
        ]);

        $paymentRequest = PaymentRequest::factory()->create([
            'full_name' => 'Maryam Buba',
            'matric_number' => 'GSU/17/0456',
            'program_type_name' => 'Masters',
            'payment_type_id' => $paymentType->id,
            'payment_type_name' => $paymentType->name,
        ]);

        $response = $this->get(route('student-payments.show', $paymentRequest));

        $response
            ->assertOk()
            ->assertSee('Maryam Buba')
            ->assertSee('GSU/17/0456')
            ->assertSee('Masters')
            ->assertSee('ID Card Fee');
    }

    public function test_unknown_faculty_cannot_be_submitted()
    {
        $programType = ProgramType::query()->where('name', 'Undergraduate')->firstOrFail();
        $paymentType = PaymentType::factory()->create([
            'is_active' => true,
        ]);
        $paymentType->programTypes()->sync([$programType->id]);

        $response = $this
            ->from(route('student-payments.create'))
            ->post(route('student-payments.store'), [
                'full_name' => 'Umar Ahmed',
                'matric_number' => 'GSU/18/1111',
                'email' => 'umar@example.com',
                'phone_number' => '08087654321',
                'department' => 'Accounting',
                'faculty' => 'Management Sciences',
                'program_type_id' => $programType->id,
                'graduation_session' => '2024/2025',
                'payment_type_id' => $paymentType->id,
            ]);

        $response
            ->assertRedirect(route('student-payments.create'))
            ->assertSessionHasErrors('faculty');

        $this->assertDatabaseCount('payment_requests', 0);
    }

    public function test_department_must_match_selected_faculty()
    {
        $programType = ProgramType::query()->where('name', 'Undergraduate')->firstOrFail();
        $paymentType = PaymentType::factory()->create([
            'is_active' => true,
        ]);
        $paymentType->programTypes()->sync([$programType->id]);

        $response = $this
            ->from(route('student-payments.create'))
            ->post(route('student-payments.store'), [
                'full_name' => 'Zainab Isah',
                'matric_number' => 'GSU/18/7777',
                'email' => 'zainab@example.com',
                'phone_number' => '08045678901',
                'department' => 'Computer Science',
                'faculty' => 'Faculty of Arts and Social Sciences',
                'program_type_id' => $programType->id,
                'graduation_session' => '2024/2025',
                'payment_type_id' => $paymentType->id,
            ]);

        $response
            ->assertRedirect(route('student-payments.create'))
            ->assertSessionHasErrors('department');

        $this->assertDatabaseCount('payment_requests', 0);
    }

    public function test_payment_type_must_match_selected_program_type()
    {
        $undergraduate = ProgramType::query()->where('name', 'Undergraduate')->firstOrFail();
        $masters = ProgramType::query()->where('name', 'Masters')->firstOrFail();
        $paymentType = PaymentType::factory()->create([
            'is_active' => true,
        ]);
        $paymentType->programTypes()->sync([$masters->id]);

        $response = $this
            ->from(route('student-payments.create'))
            ->post(route('student-payments.store'), [
                'full_name' => 'Khadija Bello',
                'matric_number' => 'GSU/20/4545',
                'email' => 'khadija@example.com',
                'phone_number' => '08031234567',
                'department' => 'Computer Science',
                'faculty' => 'Faculty of Sciences',
                'program_type_id' => $undergraduate->id,
                'graduation_session' => '2024/2025',
                'payment_type_id' => $paymentType->id,
            ]);

        $response
            ->assertRedirect(route('student-payments.create'))
            ->assertSessionHasErrors('payment_type_id');

        $this->assertDatabaseCount('payment_requests', 0);
    }

    public function test_website_charge_is_added_even_for_small_payments_without_triggering_paystack_flat_fee()
    {
        $programType = ProgramType::query()->where('name', 'Undergraduate')->firstOrFail();
        $paymentType = PaymentType::factory()->create([
            'name' => 'Boundary Fee',
            'amount' => 1,
            'description' => 'Boundary amount for fixed charge.',
            'is_active' => true,
        ]);
        $paymentType->programTypes()->sync([$programType->id]);

        $this->post(route('student-payments.store'), [
            'full_name' => 'Hauwa Bello',
            'matric_number' => 'GSU/21/0101',
            'email' => 'hauwa@example.com',
            'phone_number' => '08012340000',
            'department' => 'Computer Science',
            'faculty' => 'Faculty of Sciences',
            'program_type_id' => $programType->id,
            'graduation_session' => '2024/2025',
            'payment_type_id' => $paymentType->id,
        ])->assertRedirect();

        $paymentRequest = PaymentRequest::query()->latest('id')->firstOrFail();

        $this->assertSame('1.00', $paymentRequest->base_amount);
        $this->assertSame('100.00', $paymentRequest->portal_charge_amount);
        $this->assertSame('0.00', $paymentRequest->paystack_charge_amount);
        $this->assertSame('101.00', $paymentRequest->amount);
    }

    public function test_paystack_flat_fee_is_applied_when_payable_amount_reaches_2500_or_more()
    {
        $programType = ProgramType::query()->where('name', 'Undergraduate')->firstOrFail();
        $paymentType = PaymentType::factory()->create([
            'name' => 'Inclusive Threshold Fee',
            'amount' => 2400,
            'description' => 'Tests the inclusive paystack flat fee threshold.',
            'is_active' => true,
        ]);
        $paymentType->programTypes()->sync([$programType->id]);

        $this->post(route('student-payments.store'), [
            'full_name' => 'Maimuna Sani',
            'matric_number' => 'GSU/21/0202',
            'email' => 'maimuna@example.com',
            'phone_number' => '08022223333',
            'department' => 'Computer Science',
            'faculty' => 'Faculty of Sciences',
            'program_type_id' => $programType->id,
            'graduation_session' => '2024/2025',
            'payment_type_id' => $paymentType->id,
        ])->assertRedirect();

        $paymentRequest = PaymentRequest::query()->latest('id')->firstOrFail();

        $this->assertSame('2400.00', $paymentRequest->base_amount);
        $this->assertSame('100.00', $paymentRequest->portal_charge_amount);
        $this->assertSame('100.00', $paymentRequest->paystack_charge_amount);
        $this->assertSame('2600.00', $paymentRequest->amount);
    }
}
