<?php

namespace Tests\Feature\Student;

use App\Enums\PaymentRequestStatus;
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
            'amount' => '5000.00',
            'payment_status' => PaymentRequestStatus::Pending->value,
        ]);
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
        $this->assertSame('12000.00', $existingRequest->amount);
    }

    public function test_successful_payment_request_blocks_creating_another_open_request_for_same_payment_type()
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

        $response = $this
            ->from(route('student-payments.create'))
            ->post(route('student-payments.store'), [
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

        $response
            ->assertRedirect(route('student-payments.create'))
            ->assertSessionHasErrors('payment_type_id');

        $this->assertDatabaseCount('payment_requests', 1);
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
}
