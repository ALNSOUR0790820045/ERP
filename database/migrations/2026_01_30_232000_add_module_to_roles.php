<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // إضافة عمود الوحدة للأدوار
        if (!Schema::hasColumn('roles', 'module')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('module')->nullable()->after('code');
            });
        }

        // تحديث الأدوار الموجودة
        DB::table('roles')->where('code', 'like', 'tender_%')->update(['module' => 'tenders']);
        DB::table('roles')->where('code', 'like', 'contract_%')->update(['module' => 'contracts']);
        DB::table('roles')->where('code', 'like', 'project_%')->update(['module' => 'projects']);
        DB::table('roles')->where('code', 'like', 'hr_%')->update(['module' => 'hr']);
        DB::table('roles')->where('code', 'like', 'finance_%')->update(['module' => 'finance']);
        
        // الأدوار العامة
        DB::table('roles')->whereNull('module')->update(['module' => 'core']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('roles', 'module')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('module');
            });
        }
    }
};
