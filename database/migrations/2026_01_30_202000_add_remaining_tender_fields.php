<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            // نطاق التصنيف
            if (!Schema::hasColumn('tenders', 'classification_scope')) {
                $table->string('classification_scope', 255)->nullable()->after('classification_category_id');
            }
            
            // المنطقة/الحي
            if (!Schema::hasColumn('tenders', 'project_district')) {
                $table->string('project_district', 255)->nullable()->after('city');
            }
            
            // صورة الإعلان
            if (!Schema::hasColumn('tenders', 'announcement_image')) {
                $table->string('announcement_image', 500)->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropColumn([
                'classification_scope',
                'project_district',
                'announcement_image',
            ]);
        });
    }
};
