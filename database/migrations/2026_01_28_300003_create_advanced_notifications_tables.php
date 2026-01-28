<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * نظام الإشعارات المتقدم
     */
    public function up(): void
    {
        // جدول قوالب الإشعارات
        if (!Schema::hasTable('notification_templates')) {
            Schema::create('notification_templates', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('name_ar')->nullable();
                $table->text('description')->nullable();
                
                // نوع الإشعار
                $table->string('category'); // workflow, contract, payment, hr, inventory, etc.
                $table->string('event_type'); // created, updated, approved, rejected, etc.
                
                // المحتوى
                $table->string('subject');
                $table->string('subject_ar')->nullable();
                $table->text('body');
                $table->text('body_ar')->nullable();
                
                // قنوات الإرسال
                $table->boolean('send_database')->default(true);
                $table->boolean('send_email')->default(false);
                $table->boolean('send_sms')->default(false);
                $table->boolean('send_push')->default(false);
                $table->boolean('send_whatsapp')->default(false);
                
                // المتغيرات المتاحة
                $table->json('available_variables')->nullable();
                
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // جدول تفضيلات المستخدم للإشعارات
        if (!Schema::hasTable('user_notification_preferences')) {
            Schema::create('user_notification_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('category');
                $table->boolean('database_enabled')->default(true);
                $table->boolean('email_enabled')->default(true);
                $table->boolean('sms_enabled')->default(false);
                $table->boolean('push_enabled')->default(true);
                $table->boolean('whatsapp_enabled')->default(false);
                $table->json('settings')->nullable();
                $table->timestamps();
                
                $table->unique(['user_id', 'category']);
            });
        }

        // جدول سجل الإشعارات المرسلة
        if (!Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
                
                $table->string('channel'); // database, email, sms, push, whatsapp
                $table->string('subject');
                $table->text('body');
                
                $table->morphs('notifiable'); // notifiable_type, notifiable_id
                
                $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
                $table->text('error_message')->nullable();
                
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('read_at')->nullable();
                
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'status']);
                $table->index(['channel', 'status']);
            });
        }

        // جدول جدولة الإشعارات
        if (!Schema::hasTable('scheduled_notifications')) {
            Schema::create('scheduled_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
                
                $table->string('name');
                $table->text('description')->nullable();
                
                // الجدولة
                $table->enum('frequency', ['once', 'daily', 'weekly', 'monthly', 'custom']);
                $table->string('cron_expression')->nullable();
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('last_run_at')->nullable();
                $table->timestamp('next_run_at')->nullable();
                
                // المستلمين
                $table->enum('recipient_type', ['all', 'role', 'department', 'custom']);
                $table->json('recipient_ids')->nullable();
                $table->string('recipient_query')->nullable();
                
                // المحتوى
                $table->string('subject');
                $table->text('body');
                $table->json('channels')->nullable();
                
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // جدول التذكيرات
        if (!Schema::hasTable('reminders')) {
            Schema::create('reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->morphs('remindable'); // remindable_type, remindable_id
                
                $table->string('title');
                $table->text('description')->nullable();
                $table->timestamp('remind_at');
                
                $table->enum('status', ['pending', 'sent', 'snoozed', 'completed', 'cancelled'])->default('pending');
                $table->integer('snooze_count')->default(0);
                $table->timestamp('snoozed_until')->nullable();
                
                $table->json('channels')->nullable();
                $table->json('metadata')->nullable();
                
                $table->timestamps();
                
                $table->index(['user_id', 'status', 'remind_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('scheduled_notifications');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('user_notification_preferences');
        Schema::dropIfExists('notification_templates');
    }
};
