<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentRequestStatus;
use App\Models\PaymentRequest;
use App\Models\PaymentType;
use App\Models\ProgramType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_alumni_admin_can_view_the_program_type_management_page()
    {
        $admin = User::factory()->alumniAdmin()->create();

        $response = $this->actingAs($admin)->get(route('admin.program-types.index'));

        $response->assertOk();
    }

    public function test_students_cannot_access_the_program_type_management_module()
    {
        $student = User::factory()->create();

        $response = $this->actingAs($student)->get(route('admin.program-types.index'));

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    public function test_alumni_admin_can_create_a_program_type()
    {
        $admin = User::factory()->alumniAdmin()->create();

        $response = $this->actingAs($admin)->post(route('admin.program-types.store'), [
            'name' => 'Professional Postgraduate',
            'description' => 'Professional postgraduate programmes such as MBA and MPA.',
            'is_active' => true,
            'display_order' => 9,
        ]);

        $response
            ->assertRedirect(route('admin.program-types.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('program_types', [
            'name' => 'Professional Postgraduate',
            'slug' => 'professional-postgraduate',
            'description' => 'Professional postgraduate programmes such as MBA and MPA.',
            'is_active' => true,
            'display_order' => 9,
        ]);
    }

    public function test_program_type_requires_unique_name()
    {
        $admin = User::factory()->alumniAdmin()->create();

        ProgramType::query()->create([
            'name' => 'Undergraduate',
            'slug' => 'undergraduate',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.program-types.create'))
            ->post(route('admin.program-types.store'), [
                'name' => 'Undergraduate',
                'description' => 'Duplicate entry.',
                'is_active' => true,
                'display_order' => '',
            ]);

        $response
            ->assertRedirect(route('admin.program-types.create'))
            ->assertSessionHasErrors('name');
    }

    public function test_alumni_admin_can_update_status_and_edit_a_program_type()
    {
        $admin = User::factory()->alumniAdmin()->create();

        $programType = ProgramType::query()->create([
            'name' => 'Postgraduate Diploma',
            'slug' => 'postgraduate-diploma',
            'description' => 'Initial description.',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->put(route('admin.program-types.update', $programType), [
            'name' => 'Masters',
            'description' => 'Updated description.',
            'is_active' => true,
            'display_order' => 7,
        ])->assertRedirect(route('admin.program-types.index'));

        $this->actingAs($admin)->patch(route('admin.program-types.status.update', $programType), [
            'is_active' => false,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('program_types', [
            'id' => $programType->id,
            'name' => 'Masters',
            'slug' => 'masters',
            'description' => 'Updated description.',
            'is_active' => false,
            'display_order' => 7,
        ]);
    }

    public function test_alumni_admin_can_delete_a_program_type()
    {
        $admin = User::factory()->alumniAdmin()->create();

        $programType = ProgramType::query()->create([
            'name' => 'Certificate',
            'slug' => 'certificate',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.program-types.index'))
            ->delete(route('admin.program-types.destroy', $programType));

        $response
            ->assertRedirect(route('admin.program-types.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('program_types', [
            'id' => $programType->id,
        ]);
    }

    public function test_used_program_type_cannot_be_deleted()
    {
        $admin = User::factory()->alumniAdmin()->create();

        $programType = ProgramType::query()->create([
            'name' => 'Undergraduate',
            'slug' => 'undergraduate',
            'is_active' => true,
        ]);

        PaymentRequest::factory()->create([
            'program_type_id' => $programType->id,
            'program_type_name' => $programType->name,
            'payment_status' => PaymentRequestStatus::Pending,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.program-types.index'))
            ->delete(route('admin.program-types.destroy', $programType));

        $response
            ->assertRedirect(route('admin.program-types.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('program_types', [
            'id' => $programType->id,
        ]);
    }

    public function test_program_type_linked_to_a_payment_type_cannot_be_deleted()
    {
        $admin = User::factory()->alumniAdmin()->create();

        $programType = ProgramType::query()->create([
            'name' => 'Undergraduate',
            'slug' => 'undergraduate',
            'is_active' => true,
        ]);

        $paymentType = PaymentType::factory()->create();
        $paymentType->programTypes()->sync([$programType->id]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.program-types.index'))
            ->delete(route('admin.program-types.destroy', $programType));

        $response
            ->assertRedirect(route('admin.program-types.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('program_types', [
            'id' => $programType->id,
        ]);
    }
}
