<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // الأقسام
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->foreignId('parent_id')->nullable()->constrained('departments');
            
            $table->string('code', 20)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->foreignId('manager_id')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // المسميات الوظيفية
        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            
            $table->string('code', 20)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();
            
            $table->decimal('min_salary', 12, 3)->nullable();
            $table->decimal('max_salary', 12, 3)->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // الموظفين
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->foreignId('department_id')->nullable()->constrained();
            $table->foreignId('job_title_id')->nullable()->constrained();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('employee_code', 30)->unique();
            $table->string('first_name_ar');
            $table->string('last_name_ar');
            $table->string('first_name_en')->nullable();
            $table->string('last_name_en')->nullable();
            
            $table->string('national_id', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('marital_status', 20)->nullable();
            $table->string('nationality', 50)->nullable();
            
            $table->string('phone', 30)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            
            $table->date('hire_date');
            $table->string('employment_type', 30)->default('full_time');
            $table->string('employment_status', 30)->default('active');
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();
            
            $table->decimal('basic_salary', 12, 3)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained('currencies');
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('iban', 50)->nullable();
            
            $table->foreignId('direct_manager_id')->nullable()->constrained('employees');
            $table->foreignId('project_id')->nullable()->constrained();
            
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // العقود الوظيفية
        Schema::create('employment_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            
            $table->string('contract_number', 50)->unique();
            $table->string('contract_type', 30);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            
            $table->decimal('salary', 12, 3);
            $table->text('terms')->nullable();
            
            $table->string('status', 30)->default('active');
            $table->timestamps();
        });

        // البدلات والخصومات
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            
            $table->string('code', 20)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('component_type', 20); // allowance, deduction
            $table->string('calculation_type', 20)->default('fixed'); // fixed, percentage
            $table->decimal('default_value', 12, 3)->default(0);
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });

        // بدلات الموظف
        Schema::create('employee_salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('salary_components');
            
            $table->decimal('amount', 12, 3);
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // الحضور والانصراف
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained();
            $table->foreignId('project_id')->nullable()->constrained();
            
            $table->date('attendance_date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->decimal('working_hours', 5, 2)->nullable();
            $table->decimal('overtime_hours', 5, 2)->default(0);
            
            $table->string('status', 20)->default('present'); // present, absent, leave, holiday
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->unique(['employee_id', 'attendance_date']);
        });

        // الإجازات
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->integer('days_per_year')->nullable();
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // طلبات الإجازة
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained();
            $table->foreignId('leave_type_id')->constrained();
            
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('days_count');
            $table->text('reason')->nullable();
            
            $table->string('status', 30)->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            $table->timestamps();
        });

        // كشوف الرواتب
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            
            $table->string('payroll_number', 50)->unique();
            $table->integer('year');
            $table->integer('month');
            $table->date('period_start');
            $table->date('period_end');
            
            $table->decimal('total_basic', 18, 3)->default(0);
            $table->decimal('total_allowances', 18, 3)->default(0);
            $table->decimal('total_deductions', 18, 3)->default(0);
            $table->decimal('total_net', 18, 3)->default(0);
            
            $table->string('status', 30)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // بنود كشف الراتب
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained();
            
            $table->integer('working_days')->default(0);
            $table->integer('absent_days')->default(0);
            $table->decimal('overtime_hours', 6, 2)->default(0);
            
            $table->decimal('basic_salary', 12, 3)->default(0);
            $table->decimal('total_allowances', 12, 3)->default(0);
            $table->decimal('overtime_amount', 12, 3)->default(0);
            $table->decimal('total_earnings', 12, 3)->default(0);
            
            $table->decimal('total_deductions', 12, 3)->default(0);
            $table->decimal('net_salary', 12, 3)->default(0);
            
            $table->string('status', 30)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('employee_salary_components');
        Schema::dropIfExists('salary_components');
        Schema::dropIfExists('employment_contracts');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('job_titles');
        Schema::dropIfExists('departments');
    }
};
