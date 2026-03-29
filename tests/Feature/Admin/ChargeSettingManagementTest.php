<?php

namespace Tests\Feature\Admin;

use App\Enums\ChargeCalculationMode;
use App\Models\ChargeSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChargeSettingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_the_charge_settings_page()
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->get(route('admin.charge-settings.edit'));

        $response
            ->assertOk()
            ->assertSee('Charge settings');
    }

    public function test_alumni_admin_cannot_access_charge_settings()
    {
        $admin = User::factory()->alumniAdmin()->create();

        $response = $this->actingAs($admin)->get(route('admin.charge-settings.edit'));

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    public function test_super_admin_can_update_charge_settings()
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->put(route('admin.charge-settings.update'), [
            'portal_charge_mode' => ChargeCalculationMode::Percentage->value,
            'portal_charge_value' => '2.50',
            'paystack_percentage_rate' => '1.5000',
            'paystack_flat_fee' => '100.00',
            'paystack_flat_fee_threshold' => '2500.00',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success');

        $chargeSetting = ChargeSetting::query()->firstOrFail();

        $this->assertSame(ChargeCalculationMode::Percentage, $chargeSetting->portal_charge_mode);
        $this->assertSame('2.50', $chargeSetting->portal_charge_value);
        $this->assertSame('1.5000', number_format((float) $chargeSetting->paystack_percentage_rate, 4, '.', ''));
        $this->assertSame('100.00', $chargeSetting->paystack_flat_fee);
        $this->assertSame('2500.00', $chargeSetting->paystack_flat_fee_threshold);
        $this->assertSame($superAdmin->id, $chargeSetting->updated_by);
    }
}
