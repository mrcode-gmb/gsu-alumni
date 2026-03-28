<?php

use App\Enums\PaymentRequestStatus;
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
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->string('public_reference')->unique();
            $table->string('full_name');
            $table->string('matric_number')->index();
            $table->string('email')->index();
            $table->string('phone_number', 20);
            $table->string('department');
            $table->string('faculty');
            $table->string('graduation_session', 50);
            $table->foreignId('payment_type_id')->constrained()->restrictOnDelete();
            $table->string('payment_type_name');
            $table->text('payment_type_description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('payment_status')->default(PaymentRequestStatus::Pending->value)->index();
            $table->string('payment_reference')->nullable()->unique();
            $table->string('transaction_reference')->nullable()->unique();
            $table->timestamps();

            $table->index(
                ['matric_number', 'payment_type_id', 'payment_status'],
                'payreq_matric_type_status_idx',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};
