<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // الدفعة المرحلية IPC
        if (!Schema::hasTable('interim_payments')) {
            Schema::create('interim_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->foreignId('progress_certificate_id')->nullable()->constrained()->nullOnDelete();
                $table->string('ipc_number')->unique();
                $table->integer('ipc_sequence');
                $table->date('period_start');
                $table->date('period_end');
                $table->date('submission_date');
                $table->date('certification_date')->nullable();
                $table->decimal('gross_amount', 18, 3)->default(0);
                $table->decimal('retention_amount', 18, 3)->default(0);
                $table->decimal('advance_recovery', 18, 3)->default(0);
                $table->decimal('previous_certified', 18, 3)->default(0);
                $table->decimal('current_certified', 18, 3)->default(0);
                $table->decimal('vat_amount', 18, 3)->default(0);
                $table->decimal('net_amount', 18, 3)->default(0);
                $table->enum('status', ['draft', 'submitted', 'under_review', 'certified', 'paid', 'disputed'])->default('draft');
                $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('certified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // تقدم البنود BOQ
        if (!Schema::hasTable('boq_progresses')) {
            Schema::create('boq_progresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('interim_payment_id')->constrained()->cascadeOnDelete();
                $table->foreignId('boq_item_id')->constrained()->cascadeOnDelete();
                $table->decimal('contract_qty', 18, 4)->default(0);
                $table->decimal('contract_rate', 18, 3)->default(0);
                $table->decimal('contract_amount', 18, 3)->default(0);
                $table->decimal('previous_qty', 18, 4)->default(0);
                $table->decimal('previous_amount', 18, 3)->default(0);
                $table->decimal('current_qty', 18, 4)->default(0);
                $table->decimal('current_amount', 18, 3)->default(0);
                $table->decimal('cumulative_qty', 18, 4)->default(0);
                $table->decimal('cumulative_amount', 18, 3)->default(0);
                $table->decimal('remaining_qty', 18, 4)->default(0);
                $table->decimal('percent_complete', 5, 2)->default(0);
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }

        // تقييم مواد الموقع
        if (!Schema::hasTable('material_on_site_valuations')) {
            Schema::create('material_on_site_valuations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('interim_payment_id')->constrained()->cascadeOnDelete();
                $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
                $table->string('material_description');
                $table->decimal('quantity', 18, 4);
                $table->string('unit');
                $table->decimal('unit_rate', 18, 3);
                $table->decimal('amount', 18, 3);
                $table->decimal('previous_amount', 18, 3)->default(0);
                $table->decimal('current_amount', 18, 3)->default(0);
                $table->string('location')->nullable();
                $table->date('delivery_date')->nullable();
                $table->boolean('is_incorporated')->default(false);
                $table->timestamps();
            });
        }

        // خصم المحتجزات
        if (!Schema::hasTable('retention_deductions')) {
            Schema::create('retention_deductions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('interim_payment_id')->constrained()->cascadeOnDelete();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->decimal('retention_percent', 5, 2);
                $table->decimal('gross_work_value', 18, 3);
                $table->decimal('retention_amount', 18, 3);
                $table->decimal('previous_retention', 18, 3)->default(0);
                $table->decimal('current_retention', 18, 3)->default(0);
                $table->decimal('cumulative_retention', 18, 3)->default(0);
                $table->decimal('max_retention', 18, 3)->nullable();
                $table->boolean('max_reached')->default(false);
                $table->timestamps();
            });
        }

        // استرداد الدفعة المقدمة
        if (!Schema::hasTable('advance_recoveries')) {
            Schema::create('advance_recoveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('interim_payment_id')->constrained()->cascadeOnDelete();
                $table->foreignId('contract_advance_payment_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('advance_amount', 18, 3);
                $table->decimal('recovery_percent', 5, 2);
                $table->decimal('gross_work_value', 18, 3);
                $table->decimal('recovery_amount', 18, 3);
                $table->decimal('previous_recovered', 18, 3)->default(0);
                $table->decimal('current_recovery', 18, 3)->default(0);
                $table->decimal('cumulative_recovered', 18, 3)->default(0);
                $table->decimal('balance_remaining', 18, 3)->default(0);
                $table->timestamps();
            });
        }

        // حساب تعديل السعر
        if (!Schema::hasTable('price_adjustment_calculations')) {
            Schema::create('price_adjustment_calculations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('interim_payment_id')->constrained()->cascadeOnDelete();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->decimal('work_value', 18, 3);
                $table->decimal('fixed_element', 5, 4)->default(0.15);
                $table->decimal('labor_element', 5, 4)->default(0);
                $table->decimal('material_element', 5, 4)->default(0);
                $table->decimal('equipment_element', 5, 4)->default(0);
                $table->decimal('labor_index_base', 10, 4)->nullable();
                $table->decimal('labor_index_current', 10, 4)->nullable();
                $table->decimal('material_index_base', 10, 4)->nullable();
                $table->decimal('material_index_current', 10, 4)->nullable();
                $table->decimal('equipment_index_base', 10, 4)->nullable();
                $table->decimal('equipment_index_current', 10, 4)->nullable();
                $table->decimal('adjustment_factor', 10, 6)->default(0);
                $table->decimal('adjustment_amount', 18, 3)->default(0);
                $table->text('calculation_details')->nullable();
                $table->timestamps();
            });
        }

        // الحساب الختامي
        if (!Schema::hasTable('final_accounts')) {
            Schema::create('final_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->string('reference_number')->unique();
                $table->date('preparation_date');
                $table->decimal('original_contract_value', 18, 3);
                $table->decimal('variations_value', 18, 3)->default(0);
                $table->decimal('claims_value', 18, 3)->default(0);
                $table->decimal('price_adjustments', 18, 3)->default(0);
                $table->decimal('final_contract_value', 18, 3);
                $table->decimal('total_certified', 18, 3)->default(0);
                $table->decimal('total_paid', 18, 3)->default(0);
                $table->decimal('retention_held', 18, 3)->default(0);
                $table->decimal('retention_released', 18, 3)->default(0);
                $table->decimal('penalties_deducted', 18, 3)->default(0);
                $table->decimal('bonuses_added', 18, 3)->default(0);
                $table->decimal('final_amount_due', 18, 3);
                $table->enum('status', ['draft', 'submitted', 'under_review', 'agreed', 'disputed', 'final'])->default('draft');
                $table->text('notes')->nullable();
                $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // بنود الحساب الختامي
        if (!Schema::hasTable('final_account_items')) {
            Schema::create('final_account_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('final_account_id')->constrained()->cascadeOnDelete();
                $table->enum('item_type', ['original', 'variation', 'claim', 'daywork', 'adjustment'])->default('original');
                $table->string('reference')->nullable();
                $table->string('description');
                $table->decimal('contract_amount', 18, 3)->default(0);
                $table->decimal('final_amount', 18, 3)->default(0);
                $table->decimal('variance', 18, 3)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // موافقات الشهادات
        if (!Schema::hasTable('certificate_approvals')) {
            Schema::create('certificate_approvals', function (Blueprint $table) {
                $table->id();
                $table->morphs('certifiable');
                $table->integer('approval_level');
                $table->string('approver_role');
                $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('action', ['pending', 'approved', 'rejected', 'returned'])->default('pending');
                $table->text('comments')->nullable();
                $table->timestamp('action_at')->nullable();
                $table->timestamps();
            });
        }

        // حالة الدفع
        if (!Schema::hasTable('payment_statuses')) {
            Schema::create('payment_statuses', function (Blueprint $table) {
                $table->id();
                $table->morphs('payable');
                $table->decimal('certified_amount', 18, 3);
                $table->decimal('paid_amount', 18, 3)->default(0);
                $table->decimal('balance', 18, 3)->default(0);
                $table->date('due_date')->nullable();
                $table->date('payment_date')->nullable();
                $table->string('payment_reference')->nullable();
                $table->enum('status', ['pending', 'partial', 'paid', 'overdue'])->default('pending');
                $table->integer('days_overdue')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_statuses');
        Schema::dropIfExists('certificate_approvals');
        Schema::dropIfExists('final_account_items');
        Schema::dropIfExists('final_accounts');
        Schema::dropIfExists('price_adjustment_calculations');
        Schema::dropIfExists('advance_recoveries');
        Schema::dropIfExists('retention_deductions');
        Schema::dropIfExists('material_on_site_valuations');
        Schema::dropIfExists('boq_progresses');
        Schema::dropIfExists('interim_payments');
    }
};
