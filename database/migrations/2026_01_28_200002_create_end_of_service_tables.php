<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * نظام مكافأة نهاية الخدمة
     * End of Service Indemnity System (Jordan Labor Law)
     */
    public function up(): void
    {
        // جدول إعدادات نهاية الخدمة
        Schema::create('end_of_service_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->string('name');
            
            // معدلات الحساب حسب قانون العمل الأردني
            $table->decimal('rate_per_year', 8, 4)->default(1); // شهر عن كل سنة
            $table->decimal('max_months', 8, 2)->nullable(); // الحد الأقصى بالأشهر
            
            // خيارات الحساب
            $table->enum('calculation_basis', ['basic_salary', 'gross_salary', 'last_salary'])->default('basic_salary');
            $table->boolean('include_allowances')->default(false);
            $table->json('included_allowances')->nullable(); // قائمة البدلات المشمولة
            
            // خيارات الاستحقاق
            $table->integer('min_service_months')->default(12); // الحد الأدنى للخدمة
            $table->boolean('prorate_partial_years')->default(true);
            
            // إعدادات الاستقالة
            $table->decimal('resignation_rate_1_5_years', 8, 4)->default(0.333); // ثلث
            $table->decimal('resignation_rate_5_10_years', 8, 4)->default(0.666); // ثلثين
            $table->decimal('resignation_rate_over_10_years', 8, 4)->default(1); // كامل
            
            // إعدادات الفصل
            $table->boolean('dismissal_without_cause_full', 8, 4)->default(true);
            
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['year']);
        });

        // جدول حسابات نهاية الخدمة
        Schema::create('end_of_service_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('settings_id')->nullable()->constrained('end_of_service_settings')->nullOnDelete();
            $table->string('calculation_number')->unique();
            
            // بيانات الخدمة
            $table->date('hire_date');
            $table->date('termination_date');
            $table->integer('service_years');
            $table->integer('service_months');
            $table->integer('service_days');
            $table->decimal('total_service_years', 8, 4); // إجمالي سنوات الخدمة بالكسور
            
            // سبب إنهاء الخدمة
            $table->enum('termination_type', [
                'resignation', // استقالة
                'dismissal_with_cause', // فصل مع سبب
                'dismissal_without_cause', // فصل بدون سبب
                'contract_end', // انتهاء العقد
                'retirement', // تقاعد
                'death', // وفاة
                'disability', // عجز
                'company_closure', // إغلاق الشركة
            ]);
            
            // بيانات الراتب
            $table->decimal('basic_salary', 15, 3);
            $table->decimal('total_allowances', 15, 3)->default(0);
            $table->decimal('calculation_salary', 15, 3); // الراتب المستخدم في الحساب
            
            // تفاصيل الحساب
            $table->decimal('rate_applied', 8, 4); // النسبة المطبقة
            $table->decimal('gross_entitlement', 15, 3); // الاستحقاق الإجمالي
            
            // الخصومات
            $table->decimal('loan_deductions', 15, 3)->default(0);
            $table->decimal('advance_deductions', 15, 3)->default(0);
            $table->decimal('other_deductions', 15, 3)->default(0);
            $table->text('deduction_notes')->nullable();
            
            // صافي الاستحقاق
            $table->decimal('net_entitlement', 15, 3);
            
            // حالة الموافقة
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            
            // بيانات الدفع
            $table->date('payment_date')->nullable();
            $table->string('payment_reference')->nullable();
            $table->foreignId('payment_voucher_id')->nullable();
            
            $table->text('notes')->nullable();
            $table->json('calculation_breakdown')->nullable(); // تفاصيل الحساب
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['employee_id', 'status']);
        });

        // جدول المخصصات (Provisions)
        Schema::create('end_of_service_provisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->integer('year');
            $table->integer('month');
            
            // بيانات الحساب
            $table->decimal('opening_balance', 15, 3)->default(0);
            $table->decimal('monthly_provision', 15, 3); // المخصص الشهري
            $table->decimal('adjustment', 15, 3)->default(0);
            $table->decimal('closing_balance', 15, 3);
            
            // بيانات الموظف في وقت الحساب
            $table->decimal('salary_at_date', 15, 3);
            $table->decimal('service_years_at_date', 8, 4);
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['employee_id', 'year', 'month']);
        });

        // جدول سجل التغييرات
        Schema::create('end_of_service_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calculation_id')->constrained('end_of_service_calculations')->cascadeOnDelete();
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('end_of_service_logs');
        Schema::dropIfExists('end_of_service_provisions');
        Schema::dropIfExists('end_of_service_calculations');
        Schema::dropIfExists('end_of_service_settings');
    }
};
