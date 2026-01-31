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
        Schema::table('roles', function (Blueprint $table) {
            // نوع الدور: system=نظام، job=وظيفي، tender=عطاءات
            $table->enum('type', ['system', 'job', 'tender'])->default('job')->after('name');
            $table->string('icon', 50)->nullable()->after('description');
            $table->string('color', 20)->nullable()->after('icon');
        });
        
        // تحديث الأدوار الحالية لتكون من نوع tender
        DB::table('roles')->where('name', 'super_admin')->update(['type' => 'system']);
        DB::table('roles')->where('name', '!=', 'super_admin')->update(['type' => 'tender']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['type', 'icon', 'color']);
        });
    }
};
