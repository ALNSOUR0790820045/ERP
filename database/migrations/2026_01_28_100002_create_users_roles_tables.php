<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول الأدوار
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name_ar', 100);
            $table->string('name_en', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->integer('level')->default(0);
            $table->timestamps();
        });

        // جدول الصلاحيات
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('module', 50);
            $table->string('resource', 50);
            $table->string('action', 50);
            $table->string('name_ar', 100);
            $table->string('name_en', 100)->nullable();
            $table->timestamps();
        });

        // جدول صلاحيات الأدوار
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['role_id', 'permission_id']);
        });

        // إضافة أعمدة للمستخدمين
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 100)->nullable()->after('id');
            $table->string('name_ar')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_ar');
            $table->string('phone', 50)->nullable()->after('email');
            $table->foreignId('employee_id')->nullable()->after('phone');
            $table->foreignId('branch_id')->nullable()->after('employee_id');
            $table->foreignId('role_id')->nullable()->after('branch_id');
            $table->string('language', 5)->default('ar')->after('role_id');
            $table->string('timezone', 50)->default('Asia/Amman')->after('language');
            $table->boolean('is_active')->default(true)->after('timezone');
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->boolean('two_factor_enabled')->default(false)->after('must_change_password');
            $table->timestamp('last_login_at')->nullable()->after('two_factor_enabled');
            $table->string('avatar', 500)->nullable()->after('last_login_at');
        });

        // جدول مجموعات المستخدمين
        Schema::create('user_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // جدول أعضاء المجموعات
        Schema::create('user_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['user_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_group_members');
        Schema::dropIfExists('user_groups');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'name_ar', 'name_en', 'phone', 'employee_id',
                'branch_id', 'role_id', 'language', 'timezone', 'is_active',
                'must_change_password', 'two_factor_enabled', 'last_login_at', 'avatar'
            ]);
        });
        
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
