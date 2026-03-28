<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\PaymentRequest;
use App\Models\PaymentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_alumni_admin_can_view_the_faculty_management_page()
    {
        $admin = User::factory()->alumniAdmin()->create();

        $response = $this->actingAs($admin)->get(route('admin.faculties.index'));

        $response->assertOk();
    }

    public function test_alumni_admin_can_create_a_faculty()
    {
        $admin = User::factory()->alumniAdmin()->create();

        $response = $this->actingAs($admin)->post(route('admin.faculties.store'), [
            'name' => 'Faculty of Health Sciences',
            'is_active' => true,
            'display_order' => 10,
        ]);

        $response
            ->assertRedirect(route('admin.faculties.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('faculties', [
            'name' => 'Faculty of Health Sciences',
            'slug' => 'faculty-of-health-sciences',
            'is_active' => true,
            'display_order' => 10,
        ]);
    }

    public function test_faculty_requires_unique_name()
    {
        $admin = User::factory()->alumniAdmin()->create();

        Faculty::query()->create([
            'name' => 'Faculty of Sciences',
            'slug' => 'faculty-of-sciences',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.faculties.create'))
            ->post(route('admin.faculties.store'), [
                'name' => 'Faculty of Sciences',
                'is_active' => true,
                'display_order' => '',
            ]);

        $response
            ->assertRedirect(route('admin.faculties.create'))
            ->assertSessionHasErrors('name');
    }

    public function test_alumni_admin_can_update_status_and_edit_a_faculty()
    {
        $admin = User::factory()->alumniAdmin()->create();

        $faculty = Faculty::query()->create([
            'name' => 'Faculty of Sciences',
            'slug' => 'faculty-of-sciences',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->put(route('admin.faculties.update', $faculty), [
            'name' => 'Faculty of Natural Sciences',
            'is_active' => true,
            'display_order' => 2,
        ])->assertRedirect(route('admin.faculties.index'));

        $this->actingAs($admin)->patch(route('admin.faculties.status.update', $faculty), [
            'is_active' => false,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('faculties', [
            'id' => $faculty->id,
            'name' => 'Faculty of Natural Sciences',
            'slug' => 'faculty-of-natural-sciences',
            'is_active' => false,
            'display_order' => 2,
        ]);
    }

    public function test_faculty_with_departments_cannot_be_deleted()
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
            ->from(route('admin.faculties.index'))
            ->delete(route('admin.faculties.destroy', $faculty));

        $response
            ->assertRedirect(route('admin.faculties.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('faculties', [
            'id' => $faculty->id,
        ]);
    }

    public function test_used_faculty_cannot_be_renamed_or_deleted()
    {
        $admin = User::factory()->alumniAdmin()->create();
        $paymentType = PaymentType::factory()->create();

        $faculty = Faculty::query()->create([
            'name' => 'Faculty of Sciences',
            'slug' => 'faculty-of-sciences',
            'is_active' => true,
        ]);

        PaymentRequest::factory()->create([
            'faculty' => $faculty->name,
            'payment_type_id' => $paymentType->id,
            'payment_type_name' => $paymentType->name,
            'payment_type_description' => $paymentType->description,
            'amount' => $paymentType->amount,
        ]);

        $updateResponse = $this
            ->actingAs($admin)
            ->from(route('admin.faculties.edit', $faculty))
            ->put(route('admin.faculties.update', $faculty), [
                'name' => 'Faculty of Applied Sciences',
                'is_active' => true,
                'display_order' => '',
            ]);

        $updateResponse
            ->assertRedirect(route('admin.faculties.edit', $faculty))
            ->assertSessionHasErrors('name');

        $deleteResponse = $this
            ->actingAs($admin)
            ->from(route('admin.faculties.index'))
            ->delete(route('admin.faculties.destroy', $faculty));

        $deleteResponse
            ->assertRedirect(route('admin.faculties.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('faculties', [
            'id' => $faculty->id,
            'name' => 'Faculty of Sciences',
        ]);
    }
}
