<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // فواتير الموردين
        if (!Schema::hasTable('supplier_invoices')) {
            Schema::create('supplier_invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number')->unique();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
                $table->string('supplier_invoice_number')->nullable();
                $table->date('invoice_date');
                $table->date('due_date');
                $table->date('received_date')->nullable();
                $table->decimal('subtotal', 15, 3)->default(0);
                $table->decimal('tax_amount', 15, 3)->default(0);
                $table->decimal('discount_amount', 15, 3)->default(0);
                $table->decimal('total_amount', 15, 3)->default(0);
                $table->decimal('paid_amount', 15, 3)->default(0);
                $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('exchange_rate', 10, 6)->default(1);
                $table->enum('status', ['draft', 'pending', 'approved', 'partially_paid', 'paid', 'cancelled'])->default('draft');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // دفعات الموردين
        if (!Schema::hasTable('supplier_payments')) {
            Schema::create('supplier_payments', function (Blueprint $table) {
                $table->id();
                $table->string('payment_number')->unique();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_invoice_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
                $table->date('payment_date');
                $table->decimal('amount', 15, 3);
                $table->enum('payment_method', ['cash', 'bank_transfer', 'check', 'credit_card'])->default('bank_transfer');
                $table->string('reference_number')->nullable();
                $table->string('check_number')->nullable();
                $table->date('check_date')->nullable();
                $table->enum('status', ['pending', 'completed', 'cancelled', 'returned'])->default('pending');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // مقبوضات العملاء
        if (!Schema::hasTable('customer_receipts')) {
            Schema::create('customer_receipts', function (Blueprint $table) {
                $table->id();
                $table->string('receipt_number')->unique();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
                $table->date('receipt_date');
                $table->decimal('amount', 15, 3);
                $table->enum('payment_method', ['cash', 'bank_transfer', 'check', 'credit_card'])->default('cash');
                $table->string('reference_number')->nullable();
                $table->string('check_number')->nullable();
                $table->date('check_date')->nullable();
                $table->enum('status', ['pending', 'completed', 'cancelled', 'bounced'])->default('pending');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // التحويلات البنكية
        if (!Schema::hasTable('bank_transfers')) {
            Schema::create('bank_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('transfer_number')->unique();
                $table->foreignId('from_account_id')->constrained('bank_accounts')->cascadeOnDelete();
                $table->foreignId('to_account_id')->constrained('bank_accounts')->cascadeOnDelete();
                $table->date('transfer_date');
                $table->decimal('amount', 15, 3);
                $table->decimal('fees', 15, 3)->default(0);
                $table->decimal('exchange_rate', 10, 6)->default(1);
                $table->string('reference_number')->nullable();
                $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // الشيكات
        if (!Schema::hasTable('checks')) {
            Schema::create('checks', function (Blueprint $table) {
                $table->id();
                $table->string('check_number');
                $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
                $table->enum('check_type', ['issued', 'received'])->default('issued');
                $table->date('check_date');
                $table->date('due_date');
                $table->decimal('amount', 15, 3);
                $table->string('payee_name')->nullable();
                $table->string('drawer_name')->nullable();
                $table->enum('status', ['pending', 'deposited', 'cleared', 'returned', 'cancelled', 'collected'])->default('pending');
                $table->morphs('checkable');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['bank_account_id', 'check_number']);
            });
        }

        // دورة الشيكات
        if (!Schema::hasTable('check_cycles')) {
            Schema::create('check_cycles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('check_id')->constrained()->cascadeOnDelete();
                $table->string('action'); // issued, deposited, cleared, returned, endorsed
                $table->date('action_date');
                $table->text('notes')->nullable();
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // الصناديق النقدية
        if (!Schema::hasTable('cash_boxes')) {
            Schema::create('cash_boxes', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('current_balance', 15, 3)->default(0);
                $table->decimal('max_balance', 15, 3)->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('custodian_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // إعدادات الضرائب
        if (!Schema::hasTable('tax_settings')) {
            Schema::create('tax_settings', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->enum('tax_type', ['vat', 'sales', 'income', 'withholding', 'customs', 'other']);
                $table->decimal('rate', 8, 4);
                $table->boolean('is_compound')->default(false);
                $table->boolean('is_inclusive')->default(false);
                $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->date('effective_from')->nullable();
                $table->date('effective_to')->nullable();
                $table->timestamps();
            });
        }

        // إقرار ضريبة المبيعات
        if (!Schema::hasTable('vat_returns')) {
            Schema::create('vat_returns', function (Blueprint $table) {
                $table->id();
                $table->string('reference_number')->unique();
                $table->integer('year');
                $table->integer('period'); // month or quarter
                $table->enum('period_type', ['monthly', 'quarterly'])->default('monthly');
                $table->date('period_start');
                $table->date('period_end');
                $table->date('due_date');
                $table->decimal('output_vat', 15, 3)->default(0);
                $table->decimal('input_vat', 15, 3)->default(0);
                $table->decimal('net_vat', 15, 3)->default(0);
                $table->decimal('adjustments', 15, 3)->default(0);
                $table->decimal('payable_amount', 15, 3)->default(0);
                $table->enum('status', ['draft', 'submitted', 'paid', 'amended'])->default('draft');
                $table->date('submission_date')->nullable();
                $table->date('payment_date')->nullable();
                $table->string('submission_reference')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // ضريبة الاستقطاع
        if (!Schema::hasTable('withholding_taxes')) {
            Schema::create('withholding_taxes', function (Blueprint $table) {
                $table->id();
                $table->string('reference_number')->unique();
                $table->morphs('taxable');
                $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
                $table->date('transaction_date');
                $table->decimal('base_amount', 15, 3);
                $table->decimal('tax_rate', 8, 4);
                $table->decimal('tax_amount', 15, 3);
                $table->string('tax_type')->default('services');
                $table->enum('status', ['pending', 'reported', 'paid'])->default('pending');
                $table->integer('period_month')->nullable();
                $table->integer('period_year')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // أسعار الصرف
        if (!Schema::hasTable('exchange_rates')) {
            Schema::create('exchange_rates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('from_currency_id')->constrained('currencies')->cascadeOnDelete();
                $table->foreignId('to_currency_id')->constrained('currencies')->cascadeOnDelete();
                $table->decimal('rate', 18, 8);
                $table->date('effective_date');
                $table->string('source')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['from_currency_id', 'to_currency_id', 'effective_date'], 'exchange_rate_unique');
            });
        }

        // تاريخ أسعار الصرف
        if (!Schema::hasTable('exchange_rate_histories')) {
            Schema::create('exchange_rate_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exchange_rate_id')->constrained()->cascadeOnDelete();
                $table->decimal('old_rate', 18, 8);
                $table->decimal('new_rate', 18, 8);
                $table->date('changed_date');
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // القوائم المالية
        if (!Schema::hasTable('financial_statements')) {
            Schema::create('financial_statements', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->enum('type', ['balance_sheet', 'income_statement', 'cash_flow', 'equity_statement', 'custom']);
                $table->integer('fiscal_year');
                $table->date('period_start');
                $table->date('period_end');
                $table->json('data')->nullable();
                $table->enum('status', ['draft', 'final', 'audited'])->default('draft');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        // ميزان المراجعة
        if (!Schema::hasTable('trial_balances')) {
            Schema::create('trial_balances', function (Blueprint $table) {
                $table->id();
                $table->string('reference')->unique();
                $table->date('as_of_date');
                $table->integer('fiscal_year');
                $table->json('accounts_data')->nullable();
                $table->decimal('total_debit', 18, 3)->default(0);
                $table->decimal('total_credit', 18, 3)->default(0);
                $table->boolean('is_balanced')->default(true);
                $table->enum('status', ['draft', 'final'])->default('draft');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // التدفقات النقدية
        if (!Schema::hasTable('cash_flows')) {
            Schema::create('cash_flows', function (Blueprint $table) {
                $table->id();
                $table->string('reference')->unique();
                $table->integer('fiscal_year');
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('operating_inflow', 18, 3)->default(0);
                $table->decimal('operating_outflow', 18, 3)->default(0);
                $table->decimal('investing_inflow', 18, 3)->default(0);
                $table->decimal('investing_outflow', 18, 3)->default(0);
                $table->decimal('financing_inflow', 18, 3)->default(0);
                $table->decimal('financing_outflow', 18, 3)->default(0);
                $table->decimal('net_change', 18, 3)->default(0);
                $table->decimal('opening_balance', 18, 3)->default(0);
                $table->decimal('closing_balance', 18, 3)->default(0);
                $table->json('details')->nullable();
                $table->enum('status', ['draft', 'final'])->default('draft');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // الأرصدة الافتتاحية
        if (!Schema::hasTable('opening_balances')) {
            Schema::create('opening_balances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('account_id')->constrained()->cascadeOnDelete();
                $table->foreignId('fiscal_year_id')->nullable()->constrained()->nullOnDelete();
                $table->integer('year');
                $table->decimal('debit_amount', 18, 3)->default(0);
                $table->decimal('credit_amount', 18, 3)->default(0);
                $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['account_id', 'year', 'cost_center_id'], 'opening_balance_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_balances');
        Schema::dropIfExists('cash_flows');
        Schema::dropIfExists('trial_balances');
        Schema::dropIfExists('financial_statements');
        Schema::dropIfExists('exchange_rate_histories');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('withholding_taxes');
        Schema::dropIfExists('vat_returns');
        Schema::dropIfExists('tax_settings');
        Schema::dropIfExists('cash_boxes');
        Schema::dropIfExists('check_cycles');
        Schema::dropIfExists('checks');
        Schema::dropIfExists('bank_transfers');
        Schema::dropIfExists('customer_receipts');
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('supplier_invoices');
    }
};
