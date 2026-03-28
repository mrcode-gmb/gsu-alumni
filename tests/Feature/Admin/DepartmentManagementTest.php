<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\PaymentRequest;
use App\Models\PaymentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_alumni_admin_can_view_the_department_management_page()
    {
        $admin = User::factory()->alumniAdmin()->create();

        $response = $this->actingAs($admin)->get(route('admin.departments.index'));

        $response->assertOk();
    }

    public function test_alumni_admin_can_create_a_department()
    {
        $admin = User::factory()->alumniAdmin()->create();

        $faculty = Faculty::query()->create([
            'name' => 'Faculty of Sciences',
            'slug' => 'faculty-of-sciences',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.departments.store'), [
            'faculty_id' => $faculty->id,
            'name' => 'Computer Science',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $response
            ->assertRedirect(route('admin.departments.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('departments', [
            'faculty_id' => $faculty->id,
            'name' => 'Computer Science',
            'slug' => 'computer-science',
            'is_active' => true,
            'display_order' => 1,
        ]);
    }

    public function test_department_name_must_be_unique_within_the_same_faculty()
    {
        $admin = User::factory()->alumniAdmin()->create();

        $faculty = Faculty::query()->create([
            'name' => 'Faculty of Sciences',
            'slug' => 'faculty-of-sciences',
            'is_active' => true,
        ]);

        Department::query()->create([
            'faculty_id' => $faculty->id,
            'name' => 'Computer Science',
            'slug' => 'computer-science',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.departments.create'))
            ->post(route('admin.departments.store'), [
                'faculty_id' => $faculty->id,
                'name' => 'Computer Science',
                'is_active' => true,
                'display_order' => '',
            ]);

        $response
            ->assertRedirect(route('admin.departments.create'))
            ->assertSessionHasErrors('name');
    }

    public function test_same_department_name_can_exist_under_different_faculties()
    {
        $admin = User::factory()->alumniAdmin()->create();

        $science = Faculty::query()->create([
            'name' => 'Faculty of Sciences',
            'slug' => 'faculty-of-sciences',
            'is_active' => true,
        ]);

        $education = Faculty::query()->create([
            'name' => 'Faculty of Education',
            'slug' => 'faculty-of-education',
            'is_active' => true,
        ]);

        Department::query()->create([
            'faculty_id' => $science->id,
            'name' => 'Computer Science',
            'slug' => 'computer-science',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.departments.store'), [
            'faculty_id' => $education->id,
            'name' => 'Computer Science',
            'is_active' => true,
            'display_order' => '',
        ]);

        $response
            ->assertRedirect(route('admin.departments.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('departments', [
            'faculty_id' => $education->id,
            'name' => 'Computer Science',
        ]);
    }

    public function test_alumni_admin_can_update_status_and_edit_a_department()
    {
        $admin = User::factory()->alumniAdmin()->create();

        $faculty = Faculty::query()->create([
            'name' => 'Faculty of Sciences',
            'slug' => 'faculty-of-sciences',
            'is_active' => true,
        ]);

        $department = Department::query()->create([
            'faculty_id' => $faculty->id,
            'name' => 'Computer Science',
            'slug' => 'computer-science',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->put(route('admin.departments.update', $department), [
            'faculty_id' => $faculty->id,
            'name' => 'Computing Science',
            'is_active' => true,
            'display_order' => 3,
        ])->assertRedirect(route('admin.departments.index'));

        $this->actingAs($admin)->patch(route('admin.departments.status.update', $department), [
            'is_active' => false,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'Computing Science',
            'slug' => 'computing-science',
            'is_active' => false,
            'display_order' => 3,
        ]);
    }

    public function test_used_department_cannot_be_renamed_moved_or_deleted()
    {
        $admin = User::factory()->alumniAdmin()->create();
        $paymentType = PaymentType::factory()->create();

        $science = Faculty::query()->create([
            'name' => 'Faculty of Sciences',
            'slug' => 'faculty-of-sciences',
            'is_active' => true,
        ]);

        $education = Faculty::query()->create([
            'name' => 'Faculty of Education',
            'slug' => 'faculty-of-education',
            'is_active' => true,
        ]);

        $department = Department::query()->create([
            'faculty_id' => $science->id,
            'name' => 'Computer Science',
            'slug' => 'computer-science',
            'is_active' => true,
        ]);

        PaymentRequest::factory()->create([
            'faculty' => $science->name,
            'department' => $department->name,
            'payment_type_id' => $paymentType->id,
            'payment_type_name' => $paymentType->name,
            'payment_type_description' => $paymentType->description,
            'amount' => $paymentType->amount,
        ]);

        $updateResponse = $this
            ->actingAs($admin)
            ->from(route('admin.departments.edit', $department))
            ->put(route('admin.departments.update', $department), [
                'faculty_id' => $education->id,
                'name' => 'Information Technology',
                'is_active' => true,
                'display_order' => '',
            ]);

        $updateResponse
            ->assertRedirect(route('admin.departments.edit', $department))
            ->assertSessionHasErrors('name');

        $deleteResponse = $this
            ->actingAs($admin)
            ->from(route('admin.departments.index'))
            ->delete(route('admin.departments.destroy', $department));

        $deleteResponse
            ->assertRedirect(route('admin.departments.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'faculty_id' => $science->id,
            'name' => 'Computer Science',
        ]);
    }
}
