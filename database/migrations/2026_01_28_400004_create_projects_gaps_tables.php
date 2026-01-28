<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // المسار الحرج
        if (!Schema::hasTable('critical_paths')) {
            Schema::create('critical_paths', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->date('calculation_date');
                $table->json('critical_tasks')->nullable();
                $table->integer('total_duration_days');
                $table->decimal('total_float', 8, 2)->default(0);
                $table->date('project_start');
                $table->date('project_end');
                $table->timestamps();
            });
        }

        // موازنة الموارد
        if (!Schema::hasTable('resource_levelings')) {
            Schema::create('resource_levelings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->date('leveling_date');
                $table->json('original_schedule')->nullable();
                $table->json('leveled_schedule')->nullable();
                $table->integer('original_duration');
                $table->integer('leveled_duration');
                $table->decimal('max_resource_usage', 8, 2);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // القيمة المكتسبة
        if (!Schema::hasTable('earned_values')) {
            Schema::create('earned_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->date('measurement_date');
                $table->decimal('bac', 15, 3)->comment('Budget at Completion');
                $table->decimal('pv', 15, 3)->comment('Planned Value');
                $table->decimal('ev', 15, 3)->comment('Earned Value');
                $table->decimal('ac', 15, 3)->comment('Actual Cost');
                $table->decimal('sv', 15, 3)->comment('Schedule Variance');
                $table->decimal('cv', 15, 3)->comment('Cost Variance');
                $table->decimal('spi', 8, 4)->comment('Schedule Performance Index');
                $table->decimal('cpi', 8, 4)->comment('Cost Performance Index');
                $table->decimal('eac', 15, 3)->nullable()->comment('Estimate at Completion');
                $table->decimal('etc', 15, 3)->nullable()->comment('Estimate to Complete');
                $table->decimal('vac', 15, 3)->nullable()->comment('Variance at Completion');
                $table->decimal('tcpi', 8, 4)->nullable()->comment('To-Complete Performance Index');
                $table->decimal('planned_percent', 5, 2)->default(0);
                $table->decimal('earned_percent', 5, 2)->default(0);
                $table->timestamps();
            });
        }

        // تكاليف المشروع
        if (!Schema::hasTable('project_costs')) {
            Schema::create('project_costs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->enum('cost_type', ['labor', 'material', 'equipment', 'subcontract', 'overhead', 'other'])->default('other');
                $table->string('cost_code')->nullable();
                $table->string('description');
                $table->decimal('budget_amount', 15, 3)->default(0);
                $table->decimal('committed_amount', 15, 3)->default(0);
                $table->decimal('actual_amount', 15, 3)->default(0);
                $table->decimal('forecast_amount', 15, 3)->default(0);
                $table->decimal('variance', 15, 3)->default(0);
                $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();
            });
        }

        // التكاليف الفعلية
        if (!Schema::hasTable('project_cost_actuals')) {
            Schema::create('project_cost_actuals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('project_cost_id')->nullable()->constrained()->nullOnDelete();
                $table->date('transaction_date');
                $table->string('description');
                $table->decimal('amount', 15, 3);
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // توقعات التكلفة
        if (!Schema::hasTable('project_cost_forecasts')) {
            Schema::create('project_cost_forecasts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->date('forecast_date');
                $table->integer('period_month');
                $table->integer('period_year');
                $table->decimal('planned_cost', 15, 3)->default(0);
                $table->decimal('forecast_cost', 15, 3)->default(0);
                $table->decimal('actual_cost', 15, 3)->default(0);
                $table->decimal('variance', 15, 3)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // منحنى S
        if (!Schema::hasTable('s_curves')) {
            Schema::create('s_curves', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->date('period_date');
                $table->decimal('planned_value', 15, 3)->default(0);
                $table->decimal('earned_value', 15, 3)->default(0);
                $table->decimal('actual_cost', 15, 3)->default(0);
                $table->decimal('planned_cumulative', 15, 3)->default(0);
                $table->decimal('earned_cumulative', 15, 3)->default(0);
                $table->decimal('actual_cumulative', 15, 3)->default(0);
                $table->decimal('planned_percent', 5, 2)->default(0);
                $table->decimal('earned_percent', 5, 2)->default(0);
                $table->timestamps();
            });
        }

        // توقع التدفق النقدي
        if (!Schema::hasTable('cash_flow_forecasts')) {
            Schema::create('cash_flow_forecasts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->integer('period_month');
                $table->integer('period_year');
                $table->decimal('planned_inflow', 15, 3)->default(0);
                $table->decimal('planned_outflow', 15, 3)->default(0);
                $table->decimal('actual_inflow', 15, 3)->default(0);
                $table->decimal('actual_outflow', 15, 3)->default(0);
                $table->decimal('net_planned', 15, 3)->default(0);
                $table->decimal('net_actual', 15, 3)->default(0);
                $table->decimal('cumulative_planned', 15, 3)->default(0);
                $table->decimal('cumulative_actual', 15, 3)->default(0);
                $table->timestamps();
            });
        }

        // مخاطر المشروع
        if (!Schema::hasTable('project_risks')) {
            Schema::create('project_risks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->string('risk_code')->nullable();
                $table->string('title');
                $table->text('description');
                $table->enum('category', ['technical', 'schedule', 'cost', 'resource', 'external', 'management', 'other'])->default('other');
                $table->enum('probability', ['very_low', 'low', 'medium', 'high', 'very_high'])->default('medium');
                $table->enum('impact', ['very_low', 'low', 'medium', 'high', 'very_high'])->default('medium');
                $table->integer('risk_score')->nullable();
                $table->enum('status', ['identified', 'analyzing', 'mitigating', 'monitoring', 'closed'])->default('identified');
                $table->date('identified_date');
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('mitigation_plan')->nullable();
                $table->text('contingency_plan')->nullable();
                $table->decimal('cost_impact', 15, 3)->nullable();
                $table->integer('schedule_impact_days')->nullable();
                $table->timestamps();
            });
        }

        // معالجة المخاطر
        if (!Schema::hasTable('risk_mitigations')) {
            Schema::create('risk_mitigations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_risk_id')->constrained()->cascadeOnDelete();
                $table->string('action');
                $table->text('description')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->date('due_date')->nullable();
                $table->date('completed_date')->nullable();
                $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
                $table->text('result')->nullable();
                $table->timestamps();
            });
        }

        // مشاكل المشروع
        if (!Schema::hasTable('project_issues')) {
            Schema::create('project_issues', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->string('issue_code')->nullable();
                $table->string('title');
                $table->text('description');
                $table->enum('category', ['technical', 'resource', 'schedule', 'quality', 'scope', 'communication', 'other'])->default('other');
                $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
                $table->enum('status', ['open', 'in_progress', 'resolved', 'closed', 'escalated'])->default('open');
                $table->date('reported_date');
                $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->date('target_resolution_date')->nullable();
                $table->date('actual_resolution_date')->nullable();
                $table->text('resolution')->nullable();
                $table->timestamps();
            });
        }

        // اجتماعات المشروع
        if (!Schema::hasTable('project_meetings')) {
            Schema::create('project_meetings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->enum('meeting_type', ['kickoff', 'progress', 'review', 'closure', 'stakeholder', 'technical', 'other'])->default('progress');
                $table->date('meeting_date');
                $table->time('start_time');
                $table->time('end_time')->nullable();
                $table->string('location')->nullable();
                $table->text('agenda')->nullable();
                $table->json('attendees')->nullable();
                $table->foreignId('organized_by')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['scheduled', 'completed', 'cancelled', 'postponed'])->default('scheduled');
                $table->timestamps();
            });
        }

        // محاضر الاجتماعات
        if (!Schema::hasTable('meeting_minutes')) {
            Schema::create('meeting_minutes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_meeting_id')->constrained()->cascadeOnDelete();
                $table->text('discussions')->nullable();
                $table->text('decisions')->nullable();
                $table->json('action_items')->nullable();
                $table->text('notes')->nullable();
                $table->date('next_meeting_date')->nullable();
                $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['draft', 'sent', 'approved'])->default('draft');
                $table->timestamps();
            });
        }

        // تقويم المشروع
        if (!Schema::hasTable('project_calendars')) {
            Schema::create('project_calendars', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->json('working_days')->nullable(); // [1,2,3,4,5] for Mon-Fri
                $table->time('work_start')->default('08:00');
                $table->time('work_end')->default('17:00');
                $table->decimal('hours_per_day', 4, 2)->default(8);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        // عطل المشروع
        if (!Schema::hasTable('project_holidays')) {
            Schema::create('project_holidays', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('project_calendar_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->date('holiday_date');
                $table->boolean('is_recurring')->default(false);
                $table->timestamps();
            });
        }

        // الخطة الأسبوعية
        if (!Schema::hasTable('look_aheads')) {
            Schema::create('look_aheads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->date('week_start');
                $table->date('week_end');
                $table->integer('week_number');
                $table->json('planned_activities')->nullable();
                $table->json('completed_activities')->nullable();
                $table->json('constraints')->nullable();
                $table->text('notes')->nullable();
                $table->enum('status', ['draft', 'approved', 'completed'])->default('draft');
                $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // التقرير الأسبوعي
        if (!Schema::hasTable('weekly_reports')) {
            Schema::create('weekly_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->date('week_start');
                $table->date('week_end');
                $table->integer('week_number');
                $table->decimal('planned_progress', 5, 2)->default(0);
                $table->decimal('actual_progress', 5, 2)->default(0);
                $table->text('work_completed')->nullable();
                $table->text('work_planned')->nullable();
                $table->text('issues')->nullable();
                $table->text('delays')->nullable();
                $table->json('manpower')->nullable();
                $table->json('equipment')->nullable();
                $table->json('weather')->nullable();
                $table->enum('status', ['draft', 'submitted', 'approved'])->default('draft');
                $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // التقرير الشهري
        if (!Schema::hasTable('monthly_reports')) {
            Schema::create('monthly_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->integer('month');
                $table->integer('year');
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('planned_progress', 5, 2)->default(0);
                $table->decimal('actual_progress', 5, 2)->default(0);
                $table->decimal('budget', 15, 3)->default(0);
                $table->decimal('actual_cost', 15, 3)->default(0);
                $table->text('executive_summary')->nullable();
                $table->text('accomplishments')->nullable();
                $table->text('challenges')->nullable();
                $table->text('next_month_plan')->nullable();
                $table->json('kpis')->nullable();
                $table->json('photos')->nullable();
                $table->enum('status', ['draft', 'submitted', 'approved'])->default('draft');
                $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_reports');
        Schema::dropIfExists('weekly_reports');
        Schema::dropIfExists('look_aheads');
        Schema::dropIfExists('project_holidays');
        Schema::dropIfExists('project_calendars');
        Schema::dropIfExists('meeting_minutes');
        Schema::dropIfExists('project_meetings');
        Schema::dropIfExists('project_issues');
        Schema::dropIfExists('risk_mitigations');
        Schema::dropIfExists('project_risks');
        Schema::dropIfExists('cash_flow_forecasts');
        Schema::dropIfExists('s_curves');
        Schema::dropIfExists('project_cost_forecasts');
        Schema::dropIfExists('project_cost_actuals');
        Schema::dropIfExists('project_costs');
        Schema::dropIfExists('earned_values');
        Schema::dropIfExists('resource_levelings');
        Schema::dropIfExists('critical_paths');
    }
};
