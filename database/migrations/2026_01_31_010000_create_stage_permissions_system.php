<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * نظام الصلاحيات الذكي المرتبط بالمراحل
     */
    public function up(): void
    {
        // جدول الوحدات (Modules)
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // tenders, contracts, projects, etc.
            $table->string('name_ar', 100);
            $table->string('name_en', 100)->nullable();
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->default('gray');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // جدول موارد الوحدات (Module Resources)
        Schema::create('module_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50); // tender, bond, site_visit, etc.
            $table->string('name_ar', 100);
            $table->string('name_en', 100)->nullable();
            $table->string('model_class', 255)->nullable(); // App\Models\Tender
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['module_id', 'code']);
        });

        // جدول مراحل سير العمل للوحدة (Module Stages)
        Schema::create('module_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50); // monitoring, study, decision, purchase, pricing, submission
            $table->string('name_ar', 100);
            $table->string('name_en', 100)->nullable();
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->default('gray');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['module_id', 'code']);
        });

        // جدول أنواع الصلاحيات (Permission Types)
        Schema::create('permission_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique(); // view, create, update, delete, approve, reject, escalate, delegate
            $table->string('name_ar', 50);
            $table->string('name_en', 50)->nullable();
            $table->string('icon', 50)->nullable();
            $table->enum('category', ['basic', 'workflow', 'reports'])->default('basic');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // جدول صلاحيات المستخدم على المراحل (User Stage Permissions)
        Schema::create('user_stage_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_stage_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('permission_type_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_granted')->default(true);
            $table->boolean('is_inherited')->default(false); // موروثة من الدور
            $table->timestamp('expires_at')->nullable(); // للصلاحيات المؤقتة
            $table->string('granted_by')->nullable(); // من منح هذه الصلاحية
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'module_id', 'module_stage_id', 'permission_type_id'], 'user_stage_perm_unique');
        });

        // جدول صلاحيات الدور على المراحل (Role Stage Permissions)
        Schema::create('role_stage_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_stage_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('permission_type_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_granted')->default(true);
            $table->timestamps();
            
            $table->unique(['role_id', 'module_id', 'module_stage_id', 'permission_type_id'], 'role_stage_perm_unique');
        });

        // جدول الأذونات المؤقتة (Temporary Permissions)
        Schema::create('temporary_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('permissionable_type'); // App\Models\Tender
            $table->unsignedBigInteger('permissionable_id'); // tender_id
            $table->foreignId('permission_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('granted_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['permissionable_type', 'permissionable_id']);
        });

        // جدول قوالب الصلاحيات السريعة (Permission Templates)
        Schema::create('permission_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name_ar', 100);
            $table->string('name_en', 100)->nullable();
            $table->text('description')->nullable();
            $table->json('permissions'); // مصفوفة الصلاحيات
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // تحديث جدول المستخدمين
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'permission_template_id')) {
                $table->foreignId('permission_template_id')->nullable()->constrained()->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'permission_template_id')) {
                $table->dropForeign(['permission_template_id']);
                $table->dropColumn('permission_template_id');
            }
        });

        Schema::dropIfExists('permission_templates');
        Schema::dropIfExists('temporary_permissions');
        Schema::dropIfExists('role_stage_permissions');
        Schema::dropIfExists('user_stage_permissions');
        Schema::dropIfExists('permission_types');
        Schema::dropIfExists('module_stages');
        Schema::dropIfExists('module_resources');
        Schema::dropIfExists('modules');
    }
};
