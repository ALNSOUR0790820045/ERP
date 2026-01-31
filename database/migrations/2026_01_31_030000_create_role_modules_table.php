<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * جدول ربط الأدوار بالوحدات
     * التسلسل: الدور -> الوحدات -> الشاشات
     */
    public function up(): void
    {
        // جدول الوحدات الرئيسية للنظام
        Schema::create('system_modules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // مثل: finance, projects, tenders
            $table->string('name_ar', 100);
            $table->string('name_en', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('color', 50)->default('gray');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // جدول الشاشات/الموارد لكل وحدة
        Schema::create('system_screens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('system_modules')->cascadeOnDelete();
            $table->string('code', 100); // مثل: view_invoices, create_tender
            $table->string('name_ar', 150);
            $table->string('name_en', 150)->nullable();
            $table->string('resource_class')->nullable(); // Filament Resource class
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['module_id', 'code']);
        });

        // جدول ربط الأدوار بالوحدات
        Schema::create('role_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('system_modules')->cascadeOnDelete();
            $table->boolean('full_access')->default(false); // صلاحية كاملة على الوحدة
            $table->timestamps();
            
            $table->unique(['role_id', 'module_id']);
        });

        // جدول ربط الأدوار بالشاشات (الصلاحيات التفصيلية)
        Schema::create('role_screens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('screen_id')->constrained('system_screens')->cascadeOnDelete();
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->boolean('can_export')->default(false);
            $table->boolean('can_print')->default(false);
            $table->timestamps();
            
            $table->unique(['role_id', 'screen_id']);
        });

        // إزالة عمود module من جدول roles لأنه أصبح علاقة many-to-many
        if (Schema::hasColumn('roles', 'module')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('module');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_screens');
        Schema::dropIfExists('role_modules');
        Schema::dropIfExists('system_screens');
        Schema::dropIfExists('system_modules');

        // إعادة عمود module
        Schema::table('roles', function (Blueprint $table) {
            $table->string('module', 50)->default('core')->after('description');
        });
    }
};
