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
        Schema::create('payment_type_program_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_type_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['payment_type_id', 'program_type_id'],
                'paytype_progtype_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_type_program_type');
    }
};
