<?php

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
            ->where(function ($query): void {
                $query
                    ->whereNull('paystack_flat_fee')
                    ->orWhere('paystack_flat_fee', 0)
                    ->orWhere('paystack_flat_fee', '0.00');
            })
            ->update([
                'paystack_flat_fee' => ChargeSettingService::DEFAULT_PAYSTACK_FLAT_FEE,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('charge_settings')
            ->where('paystack_flat_fee', ChargeSettingService::DEFAULT_PAYSTACK_FLAT_FEE)
            ->update([
                'paystack_flat_fee' => '0.00',
                'updated_at' => now(),
            ]);
    }
};
