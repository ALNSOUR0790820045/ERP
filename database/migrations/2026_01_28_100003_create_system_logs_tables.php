<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول سجل النشاطات
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            
            $table->string('action', 50);
            $table->string('model_type', 100);
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['model_type', 'model_id']);
            $table->index('user_id');
            $table->index('created_at');
        });

        // جدول سجل تسجيل الدخول
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            
            $table->timestamp('login_at')->useCurrent();
            $table->timestamp('logout_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device', 100)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('platform', 50)->nullable();
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->string('failure_reason')->nullable();
            
            $table->index('user_id');
            $table->index('login_at');
        });

        // جدول التسلسلات
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained();
            
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('document_type', 50);
            $table->string('prefix', 20)->nullable();
            $table->string('suffix', 20)->nullable();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->tinyInteger('min_digits')->default(4);
            $table->enum('reset_period', ['never', 'yearly', 'monthly'])->default('yearly');
            $table->boolean('include_year')->default(true);
            $table->boolean('include_branch')->default(false);
            $table->year('current_year')->nullable();
            
            $table->timestamps();
        });

        // جدول قوالب الإشعارات
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('event_type', 100);
            $table->string('subject_ar')->nullable();
            $table->string('subject_en')->nullable();
            $table->text('body_ar')->nullable();
            $table->text('body_en')->nullable();
            $table->json('variables')->nullable();
            $table->json('channels')->nullable(); // email, sms, push, database
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });

        // جدول إعدادات الإشعارات
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->string('event_type', 100);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('push_enabled')->default(true);
            $table->boolean('database_enabled')->default(true);
            
            $table->timestamps();
            
            $table->unique(['user_id', 'event_type']);
        });

        // جدول الإعدادات العامة
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained();
            
            $table->string('group', 50);
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string, integer, boolean, json
            $table->text('description')->nullable();
            
            $table->timestamps();
            
            $table->unique(['company_id', 'group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('notification_settings');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('sequences');
        Schema::dropIfExists('login_logs');
        Schema::dropIfExists('activity_logs');
    }
};
