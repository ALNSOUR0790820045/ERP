<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ساعات العمل الإضافي
        if (!Schema::hasTable('overtimes')) {
            Schema::create('overtimes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->date('overtime_date');
                $table->decimal('hours', 5, 2);
                $table->foreignId('overtime_rate_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('rate_multiplier', 5, 2)->default(1.5);
                $table->decimal('hourly_rate', 10, 3)->nullable();
                $table->decimal('amount', 15, 3)->nullable();
                $table->string('reason')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('payroll_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();
            });
        }

        // معدلات الإضافي
        if (!Schema::hasTable('overtime_rates')) {
            Schema::create('overtime_rates', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('name_ar')->nullable();
                $table->decimal('multiplier', 5, 2)->default(1.5);
                $table->enum('type', ['regular', 'weekend', 'holiday', 'night'])->default('regular');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // البدلات
        if (!Schema::hasTable('allowances')) {
            Schema::create('allowances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->foreignId('allowance_type_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->decimal('amount', 15, 3);
                $table->enum('frequency', ['monthly', 'one_time', 'yearly'])->default('monthly');
                $table->boolean('is_taxable')->default(true);
                $table->boolean('is_social_security')->default(false);
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // الاستقطاعات
        if (!Schema::hasTable('deductions')) {
            Schema::create('deductions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->foreignId('deduction_type_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->decimal('amount', 15, 3);
                $table->enum('frequency', ['monthly', 'one_time', 'installment'])->default('monthly');
                $table->integer('installment_count')->nullable();
                $table->integer('current_installment')->default(0);
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('reason')->nullable();
                $table->timestamps();
            });
        }

        // السلف والقروض
        if (!Schema::hasTable('loans')) {
            Schema::create('loans', function (Blueprint $table) {
                $table->id();
                $table->string('loan_number')->unique();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->enum('loan_type', ['advance', 'loan', 'personal'])->default('loan');
                $table->decimal('amount', 15, 3);
                $table->decimal('remaining_amount', 15, 3);
                $table->decimal('interest_rate', 5, 2)->default(0);
                $table->integer('installment_count');
                $table->decimal('installment_amount', 15, 3);
                $table->date('request_date');
                $table->date('approval_date')->nullable();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected', 'active', 'completed', 'cancelled'])->default('pending');
                $table->text('purpose')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // أقساط السلف
        if (!Schema::hasTable('loan_installments')) {
            Schema::create('loan_installments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
                $table->integer('installment_number');
                $table->date('due_date');
                $table->decimal('amount', 15, 3);
                $table->decimal('principal_amount', 15, 3)->nullable();
                $table->decimal('interest_amount', 15, 3)->default(0);
                $table->decimal('paid_amount', 15, 3)->default(0);
                $table->date('payment_date')->nullable();
                $table->enum('status', ['pending', 'paid', 'partial', 'overdue', 'waived'])->default('pending');
                $table->foreignId('payroll_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();
            });
        }

        // عقود العمل
        if (!Schema::hasTable('work_contracts')) {
            Schema::create('work_contracts', function (Blueprint $table) {
                $table->id();
                $table->string('contract_number')->unique();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->enum('contract_type', ['permanent', 'temporary', 'probation', 'part_time', 'freelance'])->default('permanent');
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->integer('probation_days')->nullable();
                $table->date('probation_end_date')->nullable();
                $table->decimal('basic_salary', 15, 3);
                $table->string('job_title');
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->text('terms_and_conditions')->nullable();
                $table->integer('notice_period_days')->default(30);
                $table->integer('annual_leave_days')->default(14);
                $table->integer('sick_leave_days')->default(14);
                $table->enum('status', ['draft', 'active', 'expired', 'terminated', 'renewed'])->default('draft');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // تجديد العقود
        if (!Schema::hasTable('work_contract_renewals')) {
            Schema::create('work_contract_renewals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('work_contract_id')->constrained()->cascadeOnDelete();
                $table->date('old_end_date');
                $table->date('new_end_date');
                $table->decimal('old_salary', 15, 3);
                $table->decimal('new_salary', 15, 3);
                $table->decimal('salary_increase_percent', 5, 2)->default(0);
                $table->date('renewal_date');
                $table->text('notes')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // وثائق الموظفين
        if (!Schema::hasTable('employee_documents')) {
            Schema::create('employee_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->string('document_type');
                $table->string('document_number')->nullable();
                $table->string('title');
                $table->string('file_path')->nullable();
                $table->date('issue_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('issuing_authority')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // التدريب
        if (!Schema::hasTable('employee_trainings')) {
            Schema::create('employee_trainings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->string('training_name');
                $table->string('provider')->nullable();
                $table->enum('type', ['internal', 'external', 'online', 'workshop', 'certification'])->default('internal');
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->integer('duration_hours')->nullable();
                $table->decimal('cost', 15, 3)->nullable();
                $table->string('location')->nullable();
                $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
                $table->string('certificate_number')->nullable();
                $table->date('certificate_expiry')->nullable();
                $table->integer('score')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // تقييم الأداء
        if (!Schema::hasTable('performance_reviews')) {
            Schema::create('performance_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
                $table->string('review_period');
                $table->date('review_date');
                $table->decimal('overall_rating', 3, 2)->nullable();
                $table->json('ratings')->nullable();
                $table->text('strengths')->nullable();
                $table->text('areas_for_improvement')->nullable();
                $table->text('goals')->nullable();
                $table->text('employee_comments')->nullable();
                $table->text('reviewer_comments')->nullable();
                $table->enum('status', ['draft', 'submitted', 'acknowledged', 'completed'])->default('draft');
                $table->timestamp('acknowledged_at')->nullable();
                $table->timestamps();
            });
        }

        // جداول الدوام
        if (!Schema::hasTable('time_sheets')) {
            Schema::create('time_sheets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
                $table->date('week_start');
                $table->date('week_end');
                $table->decimal('total_hours', 6, 2)->default(0);
                $table->decimal('regular_hours', 6, 2)->default(0);
                $table->decimal('overtime_hours', 6, 2)->default(0);
                $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        // سطور الدوام
        if (!Schema::hasTable('time_sheet_entries')) {
            Schema::create('time_sheet_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('time_sheet_id')->constrained()->cascadeOnDelete();
                $table->date('work_date');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->decimal('hours', 5, 2)->default(0);
                $table->decimal('break_hours', 5, 2)->default(0);
                $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
                $table->text('description')->nullable();
                $table->boolean('is_billable')->default(true);
                $table->timestamps();
            });
        }

        // جدول الورديات
        if (!Schema::hasTable('shift_schedules')) {
            Schema::create('shift_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
                $table->date('schedule_date');
                $table->time('start_time');
                $table->time('end_time');
                $table->boolean('is_day_off')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['employee_id', 'schedule_date']);
            });
        }

        // تقويم العطل
        if (!Schema::hasTable('holiday_calendars')) {
            Schema::create('holiday_calendars', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('name_ar')->nullable();
                $table->date('holiday_date');
                $table->enum('type', ['national', 'religious', 'company', 'optional'])->default('national');
                $table->boolean('is_paid')->default(true);
                $table->boolean('is_recurring')->default(false);
                $table->integer('year')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // نهاية الخدمة (إذا غير موجود)
        if (!Schema::hasTable('end_of_services')) {
            Schema::create('end_of_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->date('termination_date');
                $table->enum('termination_reason', ['resignation', 'retirement', 'termination', 'end_of_contract', 'death', 'other']);
                $table->decimal('years_of_service', 5, 2);
                $table->decimal('basic_salary', 15, 3);
                $table->decimal('gratuity_amount', 15, 3);
                $table->decimal('leave_balance_days', 5, 2)->default(0);
                $table->decimal('leave_encashment', 15, 3)->default(0);
                $table->decimal('other_allowances', 15, 3)->default(0);
                $table->decimal('deductions', 15, 3)->default(0);
                $table->decimal('net_amount', 15, 3);
                $table->enum('status', ['draft', 'pending', 'approved', 'paid', 'cancelled'])->default('draft');
                $table->text('notes')->nullable();
                $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // ضريبة الدخل
        if (!Schema::hasTable('income_taxes')) {
            Schema::create('income_taxes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->foreignId('payroll_id')->nullable()->constrained()->nullOnDelete();
                $table->integer('year');
                $table->integer('month');
                $table->decimal('taxable_income', 15, 3);
                $table->decimal('tax_amount', 15, 3);
                $table->decimal('cumulative_income', 15, 3)->default(0);
                $table->decimal('cumulative_tax', 15, 3)->default(0);
                $table->json('calculation_details')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('income_taxes');
        Schema::dropIfExists('end_of_services');
        Schema::dropIfExists('holiday_calendars');
        Schema::dropIfExists('shift_schedules');
        Schema::dropIfExists('time_sheet_entries');
        Schema::dropIfExists('time_sheets');
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('employee_trainings');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('work_contract_renewals');
        Schema::dropIfExists('work_contracts');
        Schema::dropIfExists('loan_installments');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('deductions');
        Schema::dropIfExists('allowances');
        Schema::dropIfExists('overtime_rates');
        Schema::dropIfExists('overtimes');
    }
};
