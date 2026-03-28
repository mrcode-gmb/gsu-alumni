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
            $table->index('department');
            $table->index('faculty');
            $table->index('graduation_session');
            $table->index('paid_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropIndex(['department']);
            $table->dropIndex(['faculty']);
            $table->dropIndex(['graduation_session']);
            $table->dropIndex(['paid_at']);
            $table->dropIndex(['created_at']);
        });
    }
};
