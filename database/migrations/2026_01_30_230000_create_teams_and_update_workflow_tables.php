<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول الفرق
        if (!Schema::hasTable('teams')) {
            Schema::create('teams', function (Blueprint $table) {
                $table->id();
                $table->string('name_ar');
                $table->string('name_en')->nullable();
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->string('type')->default('general'); // general, tender, project, department
                $table->foreignId('leader_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // جدول أعضاء الفرق
        if (!Schema::hasTable('team_members')) {
            Schema::create('team_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('role_in_team')->default('member'); // leader, member, viewer
                $table->date('joined_at')->nullable();
                $table->date('left_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->unique(['team_id', 'user_id']);
            });
        }

        // تحديث جدول خطوات سير العمل
        if (Schema::hasTable('workflow_steps')) {
            Schema::table('workflow_steps', function (Blueprint $table) {
                if (!Schema::hasColumn('workflow_steps', 'assignment_type')) {
                    $table->string('assignment_type')->default('role')->after('step_type');
                    // role = دور، team = فريق، user = مستخدم محدد، dynamic = ديناميكي (المدير المباشر)
                }
                if (!Schema::hasColumn('workflow_steps', 'assigned_role_id')) {
                    $table->foreignId('assigned_role_id')->nullable()->after('assignment_type')->constrained('roles')->nullOnDelete();
                }
                if (!Schema::hasColumn('workflow_steps', 'assigned_team_id')) {
                    $table->foreignId('assigned_team_id')->nullable()->after('assigned_role_id')->constrained('teams')->nullOnDelete();
                }
                if (!Schema::hasColumn('workflow_steps', 'assigned_user_id')) {
                    $table->foreignId('assigned_user_id')->nullable()->after('assigned_team_id')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('workflow_steps', 'dynamic_assignment')) {
                    $table->string('dynamic_assignment')->nullable()->after('assigned_user_id');
                    // direct_manager, department_head, branch_manager
                }
                if (!Schema::hasColumn('workflow_steps', 'required_permission')) {
                    $table->string('required_permission')->nullable()->after('dynamic_assignment');
                }
                if (!Schema::hasColumn('workflow_steps', 'allow_delegation')) {
                    $table->boolean('allow_delegation')->default(true)->after('required_permission');
                }
                if (!Schema::hasColumn('workflow_steps', 'auto_assign_on_create')) {
                    $table->boolean('auto_assign_on_create')->default(false)->after('allow_delegation');
                }
                if (!Schema::hasColumn('workflow_steps', 'notify_on_assignment')) {
                    $table->boolean('notify_on_assignment')->default(true)->after('auto_assign_on_create');
                }
                if (!Schema::hasColumn('workflow_steps', 'escalation_hours')) {
                    $table->integer('escalation_hours')->nullable()->after('notify_on_assignment');
                }
                if (!Schema::hasColumn('workflow_steps', 'escalate_to_role_id')) {
                    $table->foreignId('escalate_to_role_id')->nullable()->after('escalation_hours')->constrained('roles')->nullOnDelete();
                }
            });
        }

        // جدول سجل تعيينات المهام
        if (!Schema::hasTable('workflow_assignments')) {
            Schema::create('workflow_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_instance_id')->constrained()->cascadeOnDelete();
                $table->foreignId('workflow_step_id')->constrained()->cascadeOnDelete();
                $table->foreignId('assigned_to_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('pending'); // pending, accepted, rejected, completed, escalated
                $table->timestamp('assigned_at');
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('due_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_assignments');
        
        if (Schema::hasTable('workflow_steps')) {
            Schema::table('workflow_steps', function (Blueprint $table) {
                $columns = [
                    'assignment_type', 'assigned_role_id', 'assigned_team_id', 
                    'assigned_user_id', 'dynamic_assignment', 'required_permission',
                    'allow_delegation', 'auto_assign_on_create', 'notify_on_assignment',
                    'escalation_hours', 'escalate_to_role_id'
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('workflow_steps', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
        
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
    }
};
