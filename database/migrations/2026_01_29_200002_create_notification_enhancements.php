<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==========================================
        // 🔔 NOTIFICATION SERVICE ENHANCEMENTS
        // ==========================================

        // قواعد التصعيد
        if (!Schema::hasTable('escalation_rules')) {
            Schema::create('escalation_rules', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name_ar');
                $table->string('name_en');
                $table->string('entity_type');
                $table->string('trigger_condition');
                $table->json('conditions')->nullable();
                $table->integer('initial_wait_hours')->default(24);
                $table->integer('max_escalations')->default(3);
                $table->boolean('notify_requester')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // مستويات التصعيد
        if (!Schema::hasTable('escalation_levels')) {
            Schema::create('escalation_levels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rule_id')->constrained('escalation_rules')->cascadeOnDelete();
                $table->integer('level');
                $table->integer('wait_hours');
                $table->enum('escalate_to_type', ['user', 'role', 'manager', 'department_head', 'custom']);
                $table->foreignId('escalate_to_user_id')->nullable()->constrained('users');
                $table->foreignId('escalate_to_role_id')->nullable()->constrained('roles');
                $table->foreignId('notification_template_id')->nullable()->constrained('notification_templates');
                $table->json('channels')->default('["database", "email"]');
                $table->enum('action_on_timeout', ['escalate', 'auto_approve', 'auto_reject', 'close'])->default('escalate');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // سجل التصعيد
        if (!Schema::hasTable('escalation_logs')) {
            Schema::create('escalation_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rule_id')->constrained('escalation_rules');
                $table->string('escalatable_type');
                $table->unsignedBigInteger('escalatable_id');
                $table->integer('current_level');
                $table->foreignId('escalated_to_user_id')->nullable()->constrained('users');
                $table->foreignId('escalated_to_role_id')->nullable()->constrained('roles');
                $table->enum('status', ['escalated', 'resolved', 'expired', 'cancelled'])->default('escalated');
                $table->timestamp('escalated_at');
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users');
                $table->text('resolution_notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['escalatable_type', 'escalatable_id'], 'escalation_logs_morph_idx');
            });
        }

        // اشتراكات الأحداث
        if (!Schema::hasTable('event_subscriptions')) {
            Schema::create('event_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('event_type');
                $table->string('entity_type')->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->json('conditions')->nullable();
                $table->json('channels')->default('["database"]');
                $table->enum('frequency', ['immediate', 'hourly', 'daily', 'weekly'])->default('immediate');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['event_type', 'entity_type', 'entity_id'], 'event_sub_idx');
            });
        }

        // قوائم التوزيع
        if (!Schema::hasTable('distribution_lists')) {
            Schema::create('distribution_lists', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name_ar');
                $table->string('name_en');
                $table->text('description')->nullable();
                $table->enum('list_type', ['static', 'dynamic'])->default('static');
                $table->json('dynamic_query')->nullable();
                $table->foreignId('owner_id')->nullable()->constrained('users');
                $table->boolean('allow_external')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // أعضاء قوائم التوزيع
        if (!Schema::hasTable('distribution_list_members')) {
            Schema::create('distribution_list_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distribution_list_id')->constrained('distribution_lists')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users');
                $table->foreignId('role_id')->nullable()->constrained('roles');
                $table->string('external_email')->nullable();
                $table->string('external_name')->nullable();
                $table->string('external_phone')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // ملخصات الإشعارات
        if (!Schema::hasTable('notification_digests')) {
            Schema::create('notification_digests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->enum('digest_type', ['daily', 'weekly', 'monthly'])->default('daily');
                $table->date('period_start');
                $table->date('period_end');
                $table->integer('notification_count')->default(0);
                $table->json('notification_ids');
                $table->json('summary');
                $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'digest_type', 'period_start'], 'notif_digest_idx');
            });
        }

        // طابور الإشعارات
        if (!Schema::hasTable('notification_queue')) {
            Schema::create('notification_queue', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users');
                $table->foreignId('template_id')->nullable()->constrained('notification_templates');
                $table->string('channel');
                $table->string('recipient');
                $table->string('subject')->nullable();
                $table->text('body');
                $table->json('data')->nullable();
                $table->integer('priority')->default(5);
                $table->enum('status', ['pending', 'processing', 'sent', 'failed', 'cancelled'])->default('pending');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->integer('retry_count')->default(0);
                $table->timestamp('next_retry_at')->nullable();
                $table->text('error_message')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['status', 'scheduled_at']);
                $table->index(['channel', 'status']);
            });
        }

        // إعادة محاولات الإرسال
        if (!Schema::hasTable('notification_retries')) {
            Schema::create('notification_retries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('queue_id')->constrained('notification_queue')->cascadeOnDelete();
                $table->integer('attempt_number');
                $table->timestamp('attempted_at');
                $table->enum('result', ['success', 'failed']);
                $table->text('error_message')->nullable();
                $table->string('error_code')->nullable();
                $table->json('response')->nullable();
                $table->timestamps();
            });
        }

        // Webhooks
        if (!Schema::hasTable('webhooks')) {
            Schema::create('webhooks', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('url');
                $table->enum('method', ['POST', 'PUT', 'PATCH'])->default('POST');
                $table->json('headers')->nullable();
                $table->json('events');
                $table->string('secret_key')->nullable();
                $table->enum('signature_type', ['none', 'hmac_sha256', 'hmac_sha512'])->default('hmac_sha256');
                $table->boolean('verify_ssl')->default(true);
                $table->integer('timeout_seconds')->default(30);
                $table->integer('max_retries')->default(3);
                $table->enum('status', ['active', 'paused', 'failed', 'disabled'])->default('active');
                $table->integer('consecutive_failures')->default(0);
                $table->timestamp('last_triggered_at')->nullable();
                $table->timestamp('last_success_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->timestamps();
            });
        }

        // سجل Webhooks
        if (!Schema::hasTable('webhook_logs')) {
            Schema::create('webhook_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('webhook_id')->constrained('webhooks')->cascadeOnDelete();
                $table->string('event_type');
                $table->json('payload');
                $table->integer('response_code')->nullable();
                $table->text('response_body')->nullable();
                $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
                $table->integer('attempt_number')->default(1);
                $table->integer('response_time_ms')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('triggered_at');
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->index(['webhook_id', 'triggered_at']);
            });
        }

        // اشتراكات Push
        if (!Schema::hasTable('push_subscriptions')) {
            Schema::create('push_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('endpoint');
                $table->string('p256dh_key');
                $table->string('auth_key');
                $table->string('device_type')->nullable();
                $table->string('device_name')->nullable();
                $table->string('browser')->nullable();
                $table->string('os')->nullable();
                $table->timestamp('subscribed_at');
                $table->timestamp('last_used_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['user_id', 'is_active']);
            });
        }

        // إضافة أعمدة للجداول الموجودة
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'password_policy_id')) {
                    $table->unsignedBigInteger('password_policy_id')->nullable();
                }
                if (!Schema::hasColumn('users', 'password_changed_at')) {
                    $table->timestamp('password_changed_at')->nullable();
                }
                if (!Schema::hasColumn('users', 'failed_login_attempts')) {
                    $table->integer('failed_login_attempts')->default(0);
                }
                if (!Schema::hasColumn('users', 'locked_until')) {
                    $table->timestamp('locked_until')->nullable();
                }
                if (!Schema::hasColumn('users', 'last_activity_at')) {
                    $table->timestamp('last_activity_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (!Schema::hasColumn('roles', 'password_policy_id')) {
                    $table->unsignedBigInteger('password_policy_id')->nullable();
                }
                if (!Schema::hasColumn('roles', 'max_session_count')) {
                    $table->integer('max_session_count')->default(5);
                }
                if (!Schema::hasColumn('roles', 'session_timeout_minutes')) {
                    $table->integer('session_timeout_minutes')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('notification_retries');
        Schema::dropIfExists('notification_queue');
        Schema::dropIfExists('notification_digests');
        Schema::dropIfExists('distribution_list_members');
        Schema::dropIfExists('distribution_lists');
        Schema::dropIfExists('event_subscriptions');
        Schema::dropIfExists('escalation_logs');
        Schema::dropIfExists('escalation_levels');
        Schema::dropIfExists('escalation_rules');
    }
};
