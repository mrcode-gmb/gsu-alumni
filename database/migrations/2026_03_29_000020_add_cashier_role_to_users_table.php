<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('student','alumni_admin','super_admin','cashier') NOT NULL DEFAULT 'student'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('student','alumni_admin','super_admin') NOT NULL DEFAULT 'student'");
    }
};
