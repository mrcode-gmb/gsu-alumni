<?php

use App\Enums\ChargeCalculationMode;
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
        Schema::create('charge_settings', function (Blueprint $table) {
            $table->id();
            $table->string('portal_charge_mode', 20)->default(ChargeCalculationMode::Fixed->value);
            $table->decimal('portal_charge_value', 12, 2)->default(0);
            $table->decimal('paystack_percentage_rate', 8, 4)->default(0);
            $table->decimal('paystack_flat_fee', 12, 2)->default(0);
            $table->decimal('paystack_flat_fee_threshold', 12, 2)->default(0);
            $table->decimal('paystack_charge_cap', 12, 2)->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('charge_settings')->insert([
            'portal_charge_mode' => ChargeCalculationMode::Fixed->value,
            'portal_charge_value' => 0,
            'paystack_percentage_rate' => 0,
            'paystack_flat_fee' => 0,
            'paystack_flat_fee_threshold' => 0,
            'paystack_charge_cap' => 0,
            'updated_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charge_settings');
    }
};
