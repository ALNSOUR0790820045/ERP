<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول المستخلصات
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained();
            
            $table->string('invoice_number', 50)->unique();
            $table->integer('sequence_number')->default(1);
            $table->string('invoice_type', 30);
            
            // الفترة
            $table->date('period_from');
            $table->date('period_to');
            $table->date('valuation_date');
            $table->string('reference', 100)->nullable();
            
            // القيم التراكمية
            $table->decimal('cumulative_works', 18, 3)->default(0);
            $table->decimal('cumulative_variations', 18, 3)->default(0);
            $table->decimal('cumulative_materials', 18, 3)->default(0);
            $table->decimal('cumulative_gross', 18, 3)->default(0);
            
            // القيم السابقة
            $table->decimal('previous_works', 18, 3)->default(0);
            $table->decimal('previous_variations', 18, 3)->default(0);
            $table->decimal('previous_materials', 18, 3)->default(0);
            $table->decimal('previous_gross', 18, 3)->default(0);
            
            // القيم الحالية
            $table->decimal('current_works', 18, 3)->default(0);
            $table->decimal('current_variations', 18, 3)->default(0);
            $table->decimal('current_materials', 18, 3)->default(0);
            $table->decimal('current_gross', 18, 3)->default(0);
            
            // الاستقطاعات
            $table->decimal('advance_recovery', 18, 3)->default(0);
            $table->decimal('retention_deduction', 18, 3)->default(0);
            $table->decimal('income_tax', 18, 3)->default(0);
            $table->decimal('contractor_union_fee', 18, 3)->default(0);
            $table->decimal('liquidated_damages', 18, 3)->default(0);
            $table->decimal('other_deductions', 18, 3)->default(0);
            $table->decimal('total_deductions', 18, 3)->default(0);
            
            // المبلغ الصافي
            $table->decimal('net_amount', 18, 3)->default(0);
            $table->decimal('vat_amount', 18, 3)->default(0);
            $table->decimal('final_amount', 18, 3)->default(0);
            
            // العملة
            $table->foreignId('currency_id')->nullable()->constrained('currencies');
            $table->decimal('exchange_rate', 10, 6)->default(1);
            
            // تعديل الأسعار
            $table->boolean('price_adjustment_applied')->default(false);
            $table->decimal('price_adjustment_amount', 18, 3)->default(0);
            
            // الحالة
            $table->string('status', 30)->default('draft');
            
            // التواريخ
            $table->date('submission_date')->nullable();
            $table->date('certification_date')->nullable();
            $table->date('payment_due_date')->nullable();
            $table->date('payment_date')->nullable();
            
            // الموافقات
            $table->foreignId('submitted_by')->nullable()->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->text('approval_notes')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['project_id', 'sequence_number']);
        });

        // بنود المستخلص
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_item_id')->nullable()->constrained('contract_items');
            
            $table->string('item_number', 50);
            $table->text('description');
            $table->foreignId('unit_id')->nullable()->constrained('units');
            
            $table->decimal('contract_qty', 18, 6)->default(0);
            $table->decimal('contract_rate', 18, 3)->default(0);
            $table->decimal('contract_amount', 18, 3)->default(0);
            
            $table->decimal('previous_qty', 18, 6)->default(0);
            $table->decimal('previous_amount', 18, 3)->default(0);
            
            $table->decimal('current_qty', 18, 6)->default(0);
            $table->decimal('current_amount', 18, 3)->default(0);
            
            $table->decimal('cumulative_qty', 18, 6)->default(0);
            $table->decimal('cumulative_amount', 18, 3)->default(0);
            
            $table->decimal('remaining_qty', 18, 6)->default(0);
            $table->decimal('completion_percentage', 5, 2)->default(0);
            
            $table->timestamps();
        });

        // المواد في الموقع
        Schema::create('invoice_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            
            $table->string('material_name');
            $table->string('material_code', 50)->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity', 18, 4)->default(0);
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->decimal('unit_price', 18, 3)->default(0);
            $table->decimal('total_value', 18, 3)->default(0);
            $table->decimal('claim_percentage', 5, 2)->default(80);
            $table->decimal('claimed_amount', 18, 3)->default(0);
            $table->text('delivery_notes')->nullable();
            
            $table->timestamps();
        });

        // الدفعات
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            
            $table->string('payment_number', 50);
            $table->date('payment_date');
            $table->decimal('amount', 18, 3);
            $table->string('payment_method', 50)->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('cheque_number', 50)->nullable();
            $table->text('notes')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('invoice_materials');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
