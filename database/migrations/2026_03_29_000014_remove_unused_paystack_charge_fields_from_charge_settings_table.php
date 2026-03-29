<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('charge_settings', function (Blueprint $table) {
            $table->dropColumn([
                'paystack_flat_fee_threshold',
                'paystack_charge_cap',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charge_settings', function (Blueprint $table) {
            $table->decimal('paystack_flat_fee_threshold', 12, 2)->default(0)->after('paystack_flat_fee');
            $table->decimal('paystack_charge_cap', 12, 2)->default(0)->after('paystack_flat_fee_threshold');
        });
    }
};
