<?php

use App\Enums\ChargeCalculationMode;
use App\Services\ChargeSettingService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('charge_settings')
            ->where('portal_charge_mode', ChargeCalculationMode::Fixed->value)
            ->where(function ($query): void {
                $query
                    ->whereNull('portal_charge_value')
                    ->orWhere('portal_charge_value', 0)
                    ->orWhere('portal_charge_value', '0.00');
            })
            ->update([
                'portal_charge_value' => ChargeSettingService::DEFAULT_SERVICE_CHARGE,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('charge_settings')
            ->where('portal_charge_mode', ChargeCalculationMode::Fixed->value)
            ->where('portal_charge_value', ChargeSettingService::DEFAULT_SERVICE_CHARGE)
            ->update([
                'portal_charge_value' => '0.00',
                'updated_at' => now(),
            ]);
    }
};
