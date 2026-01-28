<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * نظام إدارة القيمة المكتسبة (EVM)
     * Earned Value Management System
     */
    public function up(): void
    {
        // جدول خطوط الأساس للمشاريع - موجود مسبقاً
        if (!Schema::hasTable('project_baselines')) {
            Schema::create('project_baselines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
                $table->string('baseline_number')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->date('baseline_date');
                $table->date('planned_start_date');
                $table->date('planned_end_date');
                $table->decimal('budget_at_completion', 18, 3); // BAC
                $table->integer('planned_duration_days');
                $table->enum('status', ['draft', 'approved', 'active', 'superseded', 'cancelled'])->default('draft');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->boolean('is_current')->default(false);
                $table->json('metadata')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['project_id', 'is_current']);
            });
        }

        // جدول أنشطة خط الأساس
        if (!Schema::hasTable('baseline_activities')) {
            Schema::create('baseline_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('baseline_id')->constrained('project_baselines')->cascadeOnDelete();
            $table->string('activity_code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('baseline_activities')->nullOnDelete();
            $table->date('planned_start');
            $table->date('planned_finish');
            $table->integer('planned_duration_days');
            $table->decimal('planned_value', 18, 3); // PV for this activity
            $table->decimal('weight_percentage', 8, 4)->default(0);
            $table->integer('sequence')->default(0);
            $table->json('predecessors')->nullable();
            $table->json('resources')->nullable();
            $table->timestamps();
            
            $table->unique(['baseline_id', 'activity_code']);
        });
        }

        // جدول توزيع القيمة المخططة الزمني
        if (!Schema::hasTable('planned_value_distributions')) {
        Schema::create('planned_value_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('baseline_id')->constrained('project_baselines')->cascadeOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('baseline_activities')->cascadeOnDelete();
            $table->date('period_date'); // تاريخ الفترة
            $table->string('period_type')->default('monthly'); // weekly, monthly
            $table->decimal('planned_value', 18, 3); // PV للفترة
            $table->decimal('cumulative_pv', 18, 3); // PV التراكمي
            $table->decimal('planned_percentage', 8, 4)->default(0);
            $table->timestamps();
            
            $table->unique(['baseline_id', 'activity_id', 'period_date']);
        });
        }

        // جدول قياسات الأداء
        if (!Schema::hasTable('evm_measurements')) {
        Schema::create('evm_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('baseline_id')->constrained('project_baselines')->cascadeOnDelete();
            $table->string('measurement_number')->unique();
            $table->date('measurement_date');
            $table->date('data_date'); // تاريخ البيانات
            $table->string('period_type')->default('monthly');
            
            // القيم الأساسية
            $table->decimal('planned_value', 18, 3); // PV - القيمة المخططة
            $table->decimal('earned_value', 18, 3); // EV - القيمة المكتسبة
            $table->decimal('actual_cost', 18, 3); // AC - التكلفة الفعلية
            $table->decimal('budget_at_completion', 18, 3); // BAC - الميزانية عند الإنجاز
            
            // نسب الإنجاز
            $table->decimal('physical_progress', 8, 4)->default(0); // نسبة الإنجاز الفعلي
            $table->decimal('planned_progress', 8, 4)->default(0); // نسبة الإنجاز المخطط
            
            // الفروقات
            $table->decimal('schedule_variance', 18, 3)->default(0); // SV = EV - PV
            $table->decimal('cost_variance', 18, 3)->default(0); // CV = EV - AC
            $table->decimal('variance_at_completion', 18, 3)->default(0); // VAC = BAC - EAC
            
            // مؤشرات الأداء
            $table->decimal('schedule_performance_index', 8, 4)->default(1); // SPI = EV / PV
            $table->decimal('cost_performance_index', 8, 4)->default(1); // CPI = EV / AC
            $table->decimal('critical_ratio', 8, 4)->default(1); // CR = SPI * CPI
            $table->decimal('to_complete_performance_index', 8, 4)->nullable(); // TCPI
            
            // التنبؤات
            $table->decimal('estimate_at_completion', 18, 3)->nullable(); // EAC
            $table->decimal('estimate_to_complete', 18, 3)->nullable(); // ETC
            $table->integer('estimated_completion_days')->nullable();
            $table->date('estimated_completion_date')->nullable();
            
            // التحليل
            $table->enum('schedule_status', ['ahead', 'on_track', 'behind', 'critical'])->default('on_track');
            $table->enum('cost_status', ['under', 'on_budget', 'over', 'critical'])->default('on_budget');
            $table->enum('overall_status', ['green', 'yellow', 'red'])->default('green');
            
            $table->text('analysis_notes')->nullable();
            $table->text('corrective_actions')->nullable();
            $table->json('metadata')->nullable();
            
            $table->foreignId('measured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['project_id', 'measurement_date']);
        });
        }

        // جدول تفاصيل القياس حسب النشاط
        if (!Schema::hasTable('evm_measurement_details')) {
        Schema::create('evm_measurement_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('measurement_id')->constrained('evm_measurements')->cascadeOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('baseline_activities')->nullOnDelete();
            $table->string('activity_code')->nullable();
            $table->string('activity_name')->nullable();
            
            $table->decimal('planned_value', 18, 3)->default(0);
            $table->decimal('earned_value', 18, 3)->default(0);
            $table->decimal('actual_cost', 18, 3)->default(0);
            $table->decimal('physical_progress', 8, 4)->default(0);
            $table->decimal('schedule_variance', 18, 3)->default(0);
            $table->decimal('cost_variance', 18, 3)->default(0);
            $table->decimal('spi', 8, 4)->default(1);
            $table->decimal('cpi', 8, 4)->default(1);
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        }

        // جدول تنبيهات EVM
        if (!Schema::hasTable('evm_alerts')) {
        Schema::create('evm_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('measurement_id')->nullable()->constrained('evm_measurements')->nullOnDelete();
            $table->enum('alert_type', [
                'spi_low', 'cpi_low', 'behind_schedule', 'over_budget',
                'critical_variance', 'forecast_overrun', 'tcpi_critical'
            ]);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->string('title');
            $table->text('description');
            $table->decimal('threshold_value', 8, 4)->nullable();
            $table->decimal('actual_value', 8, 4)->nullable();
            $table->text('recommended_action')->nullable();
            $table->boolean('is_acknowledged')->default(false);
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['project_id', 'is_resolved']);
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('evm_alerts');
        Schema::dropIfExists('evm_measurement_details');
        Schema::dropIfExists('evm_measurements');
        Schema::dropIfExists('planned_value_distributions');
        Schema::dropIfExists('baseline_activities');
        Schema::dropIfExists('project_baselines');
    }
};
