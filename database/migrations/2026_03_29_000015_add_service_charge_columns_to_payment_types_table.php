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
        Schema::table('payment_types', function (Blueprint $table) {
            $table->decimal('service_charge_amount', 12, 2)->nullable()->after('amount');
            $table->decimal('paystack_charge_amount', 12, 2)->nullable()->after('service_charge_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_types', function (Blueprint $table) {
            $table->dropColumn([
                'service_charge_amount',
                'paystack_charge_amount',
            ]);
        });
    }
};
