<?php

use App\Services\ChargeSettingService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('charge_settings', function (Blueprint $table) {
            $table->decimal('paystack_flat_fee_threshold', 12, 2)
                ->default(ChargeSettingService::DEFAULT_PAYSTACK_FLAT_FEE_THRESHOLD)
                ->after('paystack_flat_fee');
        });

        DB::table('charge_settings')
            ->whereNull('paystack_flat_fee_threshold')
            ->update([
                'paystack_flat_fee_threshold' => ChargeSettingService::DEFAULT_PAYSTACK_FLAT_FEE_THRESHOLD,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charge_settings', function (Blueprint $table) {
            $table->dropColumn('paystack_flat_fee_threshold');
        });
    }
};
