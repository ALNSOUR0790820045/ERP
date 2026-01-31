<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إضافة أعمدة ناقصة لنظام الصلاحيات
     */
    public function up(): void
    {
        // إضافة أعمدة لجدول permission_types
        Schema::table('permission_types', function (Blueprint $table) {
            if (!Schema::hasColumn('permission_types', 'description')) {
                $table->text('description')->nullable()->after('name_en');
            }
            if (!Schema::hasColumn('permission_types', 'color')) {
                $table->string('color', 20)->default('gray')->after('icon');
            }
        });

        // إضافة أعمدة لجدول module_stages
        Schema::table('module_stages', function (Blueprint $table) {
            if (!Schema::hasColumn('module_stages', 'description')) {
                $table->text('description')->nullable()->after('name_en');
            }
            if (!Schema::hasColumn('module_stages', 'is_initial')) {
                $table->boolean('is_initial')->default(false)->after('sort_order');
            }
            if (!Schema::hasColumn('module_stages', 'is_final')) {
                $table->boolean('is_final')->default(false)->after('is_initial');
            }
        });

        // إضافة أعمدة لجدول module_resources
        Schema::table('module_resources', function (Blueprint $table) {
            if (!Schema::hasColumn('module_resources', 'filament_resource')) {
                $table->string('filament_resource', 255)->nullable()->after('name_en');
            }
            if (!Schema::hasColumn('module_resources', 'is_main')) {
                $table->boolean('is_main')->default(false)->after('filament_resource');
            }
        });

        // إضافة أعمدة لجدول user_stage_permissions
        Schema::table('user_stage_permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('user_stage_permissions', 'stage_id')) {
                $table->unsignedBigInteger('stage_id')->nullable()->after('module_id');
            }
            if (!Schema::hasColumn('user_stage_permissions', 'can_view_stage')) {
                $table->boolean('can_view_stage')->default(true)->after('permission_type_id');
            }
        });

        // إضافة أعمدة لجدول role_stage_permissions
        Schema::table('role_stage_permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('role_stage_permissions', 'stage_id')) {
                $table->unsignedBigInteger('stage_id')->nullable()->after('module_id');
            }
            if (!Schema::hasColumn('role_stage_permissions', 'can_view_stage')) {
                $table->boolean('can_view_stage')->default(true)->after('permission_type_id');
            }
        });

        // إضافة أعمدة لجدول temporary_permissions
        Schema::table('temporary_permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('temporary_permissions', 'module_id')) {
                $table->unsignedBigInteger('module_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('temporary_permissions', 'stage_id')) {
                $table->unsignedBigInteger('stage_id')->nullable()->after('module_id');
            }
            if (!Schema::hasColumn('temporary_permissions', 'is_revoked')) {
                $table->boolean('is_revoked')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('temporary_permissions', 'revoked_by')) {
                $table->unsignedBigInteger('revoked_by')->nullable()->after('is_revoked');
            }
            if (!Schema::hasColumn('temporary_permissions', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->after('revoked_by');
            }
        });

        // تحديث category لتكون string بدلاً من enum
        // SQLite لا يدعم تغيير نوع العمود، لذا نتركه كما هو

        // إضافة عمود module_id لجدول permission_templates
        Schema::table('permission_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('permission_templates', 'module_id')) {
                $table->unsignedBigInteger('module_id')->nullable()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permission_types', function (Blueprint $table) {
            $table->dropColumn(['description', 'color']);
        });

        Schema::table('module_stages', function (Blueprint $table) {
            $table->dropColumn(['description', 'is_initial', 'is_final']);
        });

        Schema::table('module_resources', function (Blueprint $table) {
            $table->dropColumn(['filament_resource', 'is_main']);
        });

        Schema::table('user_stage_permissions', function (Blueprint $table) {
            $table->dropColumn(['stage_id', 'can_view_stage']);
        });

        Schema::table('role_stage_permissions', function (Blueprint $table) {
            $table->dropColumn(['stage_id', 'can_view_stage']);
        });

        Schema::table('temporary_permissions', function (Blueprint $table) {
            $table->dropColumn(['module_id', 'stage_id', 'is_revoked', 'revoked_by', 'revoked_at']);
        });

        Schema::table('permission_templates', function (Blueprint $table) {
            $table->dropColumn('module_id');
        });
    }
};
