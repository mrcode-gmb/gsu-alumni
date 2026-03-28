<?php

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
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->decimal('base_amount', 12, 2)->default(0)->after('payment_type_description');
            $table->decimal('portal_charge_amount', 12, 2)->default(0)->after('base_amount');
            $table->decimal('paystack_charge_amount', 12, 2)->default(0)->after('portal_charge_amount');
            $table->json('charge_settings_snapshot')->nullable()->after('paystack_charge_amount');
        });

        DB::table('payment_requests')->update([
            'base_amount' => DB::raw('amount'),
            'portal_charge_amount' => 0,
            'paystack_charge_amount' => 0,
            'charge_settings_snapshot' => null,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn([
                'base_amount',
                'portal_charge_amount',
                'paystack_charge_amount',
                'charge_settings_snapshot',
            ]);
        });
    }
};
