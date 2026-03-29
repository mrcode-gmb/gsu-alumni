<?php

namespace Tests\Feature\Admin;

use App\Enums\ChargeCalculationMode;
use App\Enums\PaymentRequestStatus;
use App\Models\ChargeSetting;
use App\Models\PaymentRequest;
use App\Models\PaymentType;
use App\Models\ProgramType;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_alumni_admin_can_view_the_payment_type_management_page()
    {
        $admin = User::factory()->alumniAdmin()->create();

        $response = $this->actingAs($admin)->get(route('admin.payment-types.index'));

        $response->assertOk();
    }

    public function test_students_cannot_access_the_payment_type_management_module()
    {
        $student = User::factory()->create();

        $response = $this->actingAs($student)->get(route('admin.payment-types.index'));

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    public function test_alumni_admin_can_create_a_payment_type()
    {
        $admin = User::factory()->alumniAdmin()->create();
        $programTypes = ProgramType::factory()->count(2)->create();

        $response = $this->actingAs($admin)->post(route('admin.payment-types.store'), [
            'name' => 'Certificate Registration',
            'amount' => '15000',
            'description' => 'Main certificate processing payment.',
            'program_type_ids' => $programTypes->pluck('id')->all(),
            'is_active' => true,
            'display_order' => 1,
        ]);

        $response
            ->assertRedirect(route('admin.payment-types.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payment_types', [
            'name' => 'Certificate Registration',
            'amount' => '15000.00',
            'service_charge_amount' => '100.00',
            'paystack_charge_amount' => '100.00',
            'is_active' => true,
            'display_order' => 1,
        ]);

        foreach ($programTypes as $programType) {
            $this->assertDatabaseHas('payment_type_program_type', [
                'program_type_id' => $programType->id,
            ]);
        }
    }

    public function test_payment_type_requires_unique_name_and_positive_amount()
    {
        $admin = User::factory()->alumniAdmin()->create();
        $programType = ProgramType::factory()->create();
        PaymentType::factory()->create([
            'name' => 'Certificate Registration',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.payment-types.create'))
            ->post(route('admin.payment-types.store'), [
                'name' => 'Certificate Registration',
                'amount' => '0',
                'description' => 'Duplicate entry.',
                'program_type_ids' => [$programType->id],
                'is_active' => true,
                'display_order' => '',
            ]);

        $response
            ->assertRedirect(route('admin.payment-types.create'))
            ->assertSessionHasErrors(['name', 'amount']);
    }

    public function test_alumni_admin_can_update_status_and_edit_a_payment_type()
    {
        $admin = User::factory()->alumniAdmin()->create();
        $oldProgramType = ProgramType::factory()->create();
        $newProgramType = ProgramType::factory()->create();
        $paymentType = PaymentType::factory()->create([
            'name' => 'Alumni Registration',
            'is_active' => true,
        ]);
        $paymentType->programTypes()->sync([$oldProgramType->id]);

        $this->actingAs($admin)->put(route('admin.payment-types.update', $paymentType), [
            'name' => 'Alumni Registration Fee',
            'amount' => '7500.00',
            'description' => 'Updated description.',
            'program_type_ids' => [$newProgramType->id],
            'is_active' => true,
            'display_order' => 2,
        ])->assertRedirect(route('admin.payment-types.index'));

        $this->actingAs($admin)->patch(route('admin.payment-types.status.update', $paymentType), [
            'is_active' => false,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('payment_types', [
            'id' => $paymentType->id,
            'name' => 'Alumni Registration Fee',
            'amount' => '7500.00',
            'service_charge_amount' => '100.00',
            'paystack_charge_amount' => '100.00',
            'is_active' => false,
            'display_order' => 2,
        ]);

        $this->assertDatabaseHas('payment_type_program_type', [
            'payment_type_id' => $paymentType->id,
            'program_type_id' => $newProgramType->id,
        ]);
        $this->assertDatabaseMissing('payment_type_program_type', [
            'payment_type_id' => $paymentType->id,
            'program_type_id' => $oldProgramType->id,
        ]);
    }

    public function test_used_payment_type_cannot_be_deleted()
    {
        $admin = User::factory()->alumniAdmin()->create();
        $paymentType = PaymentType::factory()->create();

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_type_id');
            $table->timestamps();
        });

        DB::table('payments')->insert([
            'payment_type_id' => $paymentType->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.payment-types.index'))
            ->delete(route('admin.payment-types.destroy', $paymentType));

        $response
            ->assertRedirect(route('admin.payment-types.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('payment_types', [
            'id' => $paymentType->id,
        ]);
    }

    public function test_payment_type_linked_to_a_payment_request_cannot_be_deleted()
    {
        $admin = User::factory()->alumniAdmin()->create();
        $paymentType = PaymentType::factory()->create();

        PaymentRequest::factory()->create([
            'payment_type_id' => $paymentType->id,
            'payment_type_name' => $paymentType->name,
            'payment_type_description' => $paymentType->description,
            'amount' => $paymentType->amount,
            'payment_status' => PaymentRequestStatus::Pending,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.payment-types.index'))
            ->delete(route('admin.payment-types.destroy', $paymentType));

        $response
            ->assertRedirect(route('admin.payment-types.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('payment_types', [
            'id' => $paymentType->id,
        ]);
    }

    public function test_payment_type_saves_the_current_charge_setting_values_as_charge_amounts()
    {
        $admin = User::factory()->alumniAdmin()->create();
        $programType = ProgramType::factory()->create();

        ChargeSetting::query()->update([
            'portal_charge_mode' => ChargeCalculationMode::Percentage,
            'portal_charge_value' => '2.50',
            'paystack_percentage_rate' => '1.5000',
            'paystack_flat_fee' => '100.00',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.payment-types.store'), [
            'name' => 'Late Clearance Fee',
            'amount' => '10000',
            'description' => 'Late administrative processing.',
            'program_type_ids' => [$programType->id],
            'is_active' => true,
            'display_order' => 4,
        ]);

        $response
            ->assertRedirect(route('admin.payment-types.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payment_types', [
            'name' => 'Late Clearance Fee',
            'amount' => '10000.00',
            'service_charge_amount' => '250.00',
            'paystack_charge_amount' => '258.00',
        ]);
    }

    public function test_payment_type_create_respects_the_configured_paystack_flat_fee_threshold()
    {
        $admin = User::factory()->alumniAdmin()->create();
        $programType = ProgramType::factory()->create();

        ChargeSetting::query()->update([
            'portal_charge_mode' => ChargeCalculationMode::Fixed,
            'portal_charge_value' => '100.00',
            'paystack_percentage_rate' => '0.0000',
            'paystack_flat_fee' => '100.00',
            'paystack_flat_fee_threshold' => '3000.00',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.payment-types.store'), [
            'name' => 'Custom Threshold Fee',
            'amount' => '2800',
            'description' => 'Custom threshold charge preview.',
            'program_type_ids' => [$programType->id],
            'is_active' => true,
            'display_order' => 6,
        ]);

        $response
            ->assertRedirect(route('admin.payment-types.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payment_types', [
            'name' => 'Custom Threshold Fee',
            'amount' => '2800.00',
            'service_charge_amount' => '100.00',
            'paystack_charge_amount' => '0.00',
        ]);
    }

    public function test_default_website_charge_applies_even_for_small_amounts_without_triggering_paystack_flat_fee()
    {
        $admin = User::factory()->alumniAdmin()->create();
        $programType = ProgramType::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.payment-types.store'), [
            'name' => 'Threshold Test Fee',
            'amount' => '1.00',
            'description' => 'Boundary amount for fixed charge.',
            'program_type_ids' => [$programType->id],
            'is_active' => true,
            'display_order' => 3,
        ]);

        $response
            ->assertRedirect(route('admin.payment-types.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payment_types', [
            'name' => 'Threshold Test Fee',
            'amount' => '1.00',
            'service_charge_amount' => '100.00',
            'paystack_charge_amount' => '0.00',
        ]);
    }

    public function test_paystack_flat_fee_is_applied_when_payable_amount_reaches_2500_or_more()
    {
        $admin = User::factory()->alumniAdmin()->create();
        $programType = ProgramType::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.payment-types.store'), [
            'name' => 'Threshold Inclusive Fee',
            'amount' => '2400.00',
            'description' => 'Tests the inclusive paystack flat fee threshold.',
            'program_type_ids' => [$programType->id],
            'is_active' => true,
            'display_order' => 5,
        ]);

        $response
            ->assertRedirect(route('admin.payment-types.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payment_types', [
            'name' => 'Threshold Inclusive Fee',
            'amount' => '2400.00',
            'service_charge_amount' => '100.00',
            'paystack_charge_amount' => '100.00',
        ]);
    }
}
