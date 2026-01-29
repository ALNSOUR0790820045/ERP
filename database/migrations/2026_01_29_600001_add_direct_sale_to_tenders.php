<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إضافة حقل فرصة البيع المباشر للعطاءات
     */
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            // فرصة بيع مباشر
            if (!Schema::hasColumn('tenders', 'is_direct_sale')) {
                $table->boolean('is_direct_sale')->default(false)->after('status')
                    ->comment('فرصة بيع مباشر - بدون وثائق وكفالات');
            }
            
            // ربط بالعميل للبيع المباشر
            if (!Schema::hasColumn('tenders', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('is_direct_sale')
                    ->constrained('customers')->nullOnDelete()
                    ->comment('العميل - للبيع المباشر');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            if (Schema::hasColumn('tenders', 'customer_id')) {
                $table->dropForeign(['customer_id']);
                $table->dropColumn('customer_id');
            }
            if (Schema::hasColumn('tenders', 'is_direct_sale')) {
                $table->dropColumn('is_direct_sale');
            }
        });
    }
};
