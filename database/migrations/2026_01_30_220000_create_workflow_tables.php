<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // جدول تعريفات سير العمل
        if (!Schema::hasTable('workflow_definitions')) {
            Schema::create('workflow_definitions', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('entity_type'); // App\Models\Tender, etc.
                $table->string('trigger_event'); // created, submitted, etc.
                $table->json('conditions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // جدول خطوات سير العمل
        if (!Schema::hasTable('workflow_steps')) {
            Schema::create('workflow_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_definition_id')->constrained()->cascadeOnDelete();
                $table->integer('step_order');
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('approver_type'); // user, role, manager, department_head
                $table->foreignId('approver_id')->nullable(); // إذا كان مستخدم محدد
                $table->string('approver_role')->nullable(); // إذا كان دور
                $table->string('approval_type')->default('single'); // single, all, majority
                $table->string('required_status')->nullable();
                $table->string('next_status')->nullable();
                $table->string('next_status_approve')->nullable();
                $table->string('next_status_reject')->nullable();
                $table->string('required_permission')->nullable();
                $table->integer('time_limit_hours')->nullable();
                $table->foreignId('escalation_step_id')->nullable();
                $table->foreignId('on_approve_step_id')->nullable();
                $table->foreignId('on_reject_step_id')->nullable();
                $table->json('conditions')->nullable();
                $table->boolean('is_final')->default(false);
                $table->timestamps();
            });
        }

        // جدول تنفيذات سير العمل
        if (!Schema::hasTable('workflow_instances')) {
            Schema::create('workflow_instances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_definition_id')->constrained()->cascadeOnDelete();
                $table->string('entity_type');
                $table->unsignedBigInteger('entity_id');
                $table->foreignId('current_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
                $table->string('status')->default('pending'); // pending, in_progress, approved, rejected, cancelled
                $table->datetime('started_at')->nullable();
                $table->datetime('completed_at')->nullable();
                $table->json('data')->nullable();
                $table->timestamps();
                
                $table->index(['entity_type', 'entity_id']);
            });
        }

        // جدول إجراءات سير العمل
        if (!Schema::hasTable('workflow_actions')) {
            Schema::create('workflow_actions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_instance_id')->constrained()->cascadeOnDelete();
                $table->foreignId('workflow_step_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('action'); // approve, reject, escalate, delegate
                $table->text('comments')->nullable();
                $table->datetime('action_date');
                $table->timestamps();
            });
        }

        // جدول التفويضات
        if (!Schema::hasTable('workflow_delegations')) {
            Schema::create('workflow_delegations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('workflow_definition_id')->nullable()->constrained()->nullOnDelete();
                $table->date('start_date');
                $table->date('end_date');
                $table->text('reason')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // جدول سجل خطوات سير العمل
        if (!Schema::hasTable('workflow_step_logs')) {
            Schema::create('workflow_step_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_instance_id')->constrained()->cascadeOnDelete();
                $table->foreignId('workflow_step_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action');
                $table->string('from_status')->nullable();
                $table->string('to_status')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // جدول تنفيذ الخطوات
        if (!Schema::hasTable('workflow_step_executions')) {
            Schema::create('workflow_step_executions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_instance_id')->constrained()->cascadeOnDelete();
                $table->foreignId('workflow_step_id')->constrained()->cascadeOnDelete();
                $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('pending'); // pending, completed, skipped
                $table->datetime('started_at')->nullable();
                $table->datetime('completed_at')->nullable();
                $table->datetime('due_at')->nullable();
                $table->boolean('is_escalated')->default(false);
                $table->timestamps();
            });
        }

        // جدول قوالب سير العمل
        if (!Schema::hasTable('workflow_templates')) {
            Schema::create('workflow_templates', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('category')->nullable();
                $table->json('template_data');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_templates');
        Schema::dropIfExists('workflow_step_executions');
        Schema::dropIfExists('workflow_step_logs');
        Schema::dropIfExists('workflow_delegations');
        Schema::dropIfExists('workflow_actions');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflow_definitions');
    }
};
