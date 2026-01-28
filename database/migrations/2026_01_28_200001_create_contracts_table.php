<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول العقود الرئيسي
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->nullable()->constrained();
            $table->foreignId('company_id')->nullable()->constrained();
            
            // البيانات الأساسية
            $table->string('contract_number', 50)->unique();
            $table->string('name_ar', 500);
            $table->string('name_en', 500)->nullable();
            $table->text('description')->nullable();
            $table->text('scope_of_work')->nullable();
            
            // التصنيف
            $table->string('contract_type', 50);
            $table->string('fidic_type', 50)->nullable();
            $table->string('pricing_method', 50);
            
            // أطراف العقد - صاحب العمل
            $table->foreignId('employer_id')->nullable()->constrained('owners');
            $table->string('employer_name')->nullable();
            $table->string('employer_representative')->nullable();
            $table->string('employer_contact')->nullable();
            
            // المهندس/الاستشاري
            $table->foreignId('engineer_id')->nullable()->constrained('consultants');
            $table->string('engineer_name')->nullable();
            $table->string('engineer_representative')->nullable();
            
            // المقاول
            $table->string('contractor_name')->nullable();
            $table->string('contractor_representative')->nullable();
            $table->string('site_manager')->nullable();
            
            // التواريخ
            $table->date('award_date')->nullable();
            $table->date('signing_date')->nullable();
            $table->date('commencement_date')->nullable();
            $table->date('original_completion_date')->nullable();
            $table->date('current_completion_date')->nullable();
            $table->integer('original_duration_days')->nullable();
            $table->integer('current_duration_days')->nullable();
            $table->integer('defects_liability_months')->default(12);
            $table->date('provisional_acceptance_date')->nullable();
            $table->date('final_acceptance_date')->nullable();
            
            // القيم المالية
            $table->decimal('original_value', 18, 3)->default(0);
            $table->decimal('current_value', 18, 3)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained('currencies');
            $table->decimal('exchange_rate', 10, 6)->default(1);
            $table->decimal('vat_percentage', 5, 2)->default(0);
            $table->boolean('vat_included')->default(false);
            
            // الدفعة المقدمة
            $table->decimal('advance_payment_percentage', 5, 2)->nullable();
            $table->decimal('advance_payment_amount', 18, 3)->nullable();
            $table->string('advance_recovery_method', 50)->nullable();
            $table->decimal('advance_recovery_start', 5, 2)->nullable();
            $table->decimal('advance_recovery_rate', 5, 2)->nullable();
            
            // المحتجزات
            $table->decimal('retention_percentage', 5, 2)->default(10);
            $table->decimal('retention_limit_percentage', 5, 2)->default(5);
            $table->decimal('first_retention_release', 5, 2)->default(50);
            $table->decimal('final_retention_release', 5, 2)->default(50);
            
            // شروط الدفع
            $table->integer('payment_terms_days')->default(30);
            $table->string('billing_cycle', 50)->default('monthly');
            
            // كفالة حسن التنفيذ
            $table->decimal('performance_bond_percentage', 5, 2)->default(10);
            $table->decimal('performance_bond_amount', 18, 3)->nullable();
            $table->string('performance_bond_type', 50)->nullable();
            $table->date('performance_bond_validity')->nullable();
            
            // كفالة الدفعة المقدمة
            $table->decimal('advance_bond_percentage', 5, 2)->nullable();
            $table->decimal('advance_bond_amount', 18, 3)->nullable();
            $table->date('advance_bond_validity')->nullable();
            
            // التأمينات
            $table->boolean('car_insurance_required')->default(true);
            $table->boolean('third_party_insurance')->default(true);
            $table->boolean('professional_liability')->default(false);
            
            // تعديل الأسعار
            $table->boolean('price_adjustment_applicable')->default(false);
            $table->text('price_adjustment_formula')->nullable();
            $table->date('base_date')->nullable();
            $table->decimal('threshold_percentage', 5, 2)->nullable();
            $table->json('adjustment_indices')->nullable();
            
            // شروط خاصة
            $table->decimal('liquidated_damages_rate', 5, 4)->nullable();
            $table->decimal('liquidated_damages_max', 5, 2)->nullable();
            $table->decimal('bonus_rate', 5, 4)->nullable();
            $table->text('force_majeure_clause')->nullable();
            $table->string('dispute_resolution', 50)->nullable();
            $table->string('governing_law')->nullable();
            $table->string('arbitration_rules')->nullable();
            $table->text('special_conditions')->nullable();
            
            // الحالة
            $table->string('status', 50)->default('draft');
            $table->decimal('completion_percentage', 5, 2)->default(0);
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // جدول بنود العقد (BOQ)
        Schema::create('contract_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('contract_items');
            
            $table->string('item_number', 50);
            $table->text('description');
            $table->string('description_en')->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->string('unit_code', 20)->nullable();
            
            $table->string('item_type', 50)->default('unit_rate'); // unit_rate, lump_sum, provisional, daywork, contingency
            
            $table->decimal('contract_qty', 18, 6)->default(0);
            $table->decimal('unit_rate', 18, 3)->default(0);
            $table->decimal('total_amount', 18, 3)->default(0);
            
            $table->decimal('executed_qty', 18, 6)->default(0);
            $table->decimal('executed_amount', 18, 3)->default(0);
            $table->decimal('variation_qty', 18, 6)->default(0);
            $table->decimal('variation_amount', 18, 3)->default(0);
            
            $table->integer('sort_order')->default(0);
            $table->boolean('is_header')->default(false);
            
            $table->timestamps();
            
            $table->index(['contract_id', 'item_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_items');
        Schema::dropIfExists('contracts');
    }
};
