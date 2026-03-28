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
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->string('paystack_reference')->nullable()->unique()->after('payment_reference');
            $table->timestamp('paid_at')->nullable()->after('paystack_reference');
            $table->string('payment_channel')->nullable()->after('paid_at');
            $table->text('gateway_response')->nullable()->after('payment_channel');
            $table->json('initialization_payload')->nullable()->after('gateway_response');
            $table->json('verification_payload')->nullable()->after('initialization_payload');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn([
                'paystack_reference',
                'paid_at',
                'payment_channel',
                'gateway_response',
                'initialization_payload',
                'verification_payload',
            ]);
        });
    }
};
