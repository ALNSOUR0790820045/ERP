<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // دليل الحسابات
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts');
            
            $table->string('code', 50)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('account_type', 30); // asset, liability, equity, revenue, expense
            $table->string('account_nature', 10)->default('debit'); // debit, credit
            $table->integer('level')->default(1);
            
            $table->boolean('is_header')->default(false);
            $table->boolean('is_bank_account')->default(false);
            $table->boolean('is_cash_account')->default(false);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });

        // مراكز التكلفة
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('parent_id')->nullable()->constrained('cost_centers');
            $table->foreignId('project_id')->nullable()->constrained();
            
            $table->string('code', 30)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('center_type', 30)->nullable();
            $table->integer('level')->default(1);
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // القيود اليومية
        Schema::create('journal_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->foreignId('fiscal_year_id')->nullable()->constrained();
            $table->foreignId('fiscal_period_id')->nullable()->constrained();
            
            $table->string('voucher_number', 50)->unique();
            $table->date('voucher_date');
            $table->string('voucher_type', 30)->default('general');
            $table->text('description');
            
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number', 100)->nullable();
            
            $table->decimal('total_debit', 18, 3)->default(0);
            $table->decimal('total_credit', 18, 3)->default(0);
            
            $table->string('status', 30)->default('draft');
            $table->boolean('is_posted')->default(false);
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users');
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // بنود القيد
        Schema::create('journal_voucher_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('journal_vouchers')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts');
            $table->foreignId('cost_center_id')->nullable()->constrained();
            $table->foreignId('project_id')->nullable()->constrained();
            
            $table->integer('line_number');
            $table->text('description')->nullable();
            $table->decimal('debit_amount', 18, 3)->default(0);
            $table->decimal('credit_amount', 18, 3)->default(0);
            
            $table->foreignId('currency_id')->nullable()->constrained('currencies');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->decimal('foreign_debit', 18, 3)->nullable();
            $table->decimal('foreign_credit', 18, 3)->nullable();
            
            $table->timestamps();
        });

        // الحسابات البنكية
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts');
            $table->foreignId('currency_id')->nullable()->constrained('currencies');
            
            $table->string('bank_name');
            $table->string('branch_name')->nullable();
            $table->string('account_number', 50);
            $table->string('iban', 50)->nullable();
            $table->string('swift_code', 20)->nullable();
            
            $table->string('account_type', 30)->default('current');
            $table->decimal('opening_balance', 18, 3)->default(0);
            $table->decimal('current_balance', 18, 3)->default(0);
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // سندات القبض
        Schema::create('receipt_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('project_id')->nullable()->constrained();
            $table->foreignId('invoice_id')->nullable()->constrained();
            
            $table->string('voucher_number', 50)->unique();
            $table->date('voucher_date');
            $table->string('payment_method', 30);
            
            $table->string('payer_type', 50)->nullable();
            $table->unsignedBigInteger('payer_id')->nullable();
            $table->string('payer_name')->nullable();
            
            $table->decimal('amount', 18, 3);
            $table->foreignId('currency_id')->nullable()->constrained('currencies');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            
            $table->foreignId('bank_account_id')->nullable()->constrained();
            $table->string('check_number', 50)->nullable();
            $table->date('check_date')->nullable();
            
            $table->text('description')->nullable();
            $table->string('status', 30)->default('draft');
            $table->foreignId('journal_voucher_id')->nullable()->constrained();
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // سندات الصرف
        Schema::create('payment_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('project_id')->nullable()->constrained();
            
            $table->string('voucher_number', 50)->unique();
            $table->date('voucher_date');
            $table->string('payment_method', 30);
            
            $table->string('payee_type', 50)->nullable();
            $table->unsignedBigInteger('payee_id')->nullable();
            $table->string('payee_name')->nullable();
            
            $table->decimal('amount', 18, 3);
            $table->foreignId('currency_id')->nullable()->constrained('currencies');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            
            $table->foreignId('bank_account_id')->nullable()->constrained();
            $table->string('check_number', 50)->nullable();
            $table->date('check_date')->nullable();
            
            $table->text('description')->nullable();
            $table->string('status', 30)->default('draft');
            $table->foreignId('journal_voucher_id')->nullable()->constrained();
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // الميزانية
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('fiscal_year_id')->constrained();
            $table->foreignId('project_id')->nullable()->constrained();
            
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('budget_type', 30)->default('annual');
            
            $table->decimal('total_amount', 18, 3)->default(0);
            $table->string('status', 30)->default('draft');
            
            $table->timestamps();
        });

        // بنود الميزانية
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts');
            $table->foreignId('cost_center_id')->nullable()->constrained();
            
            $table->string('line_description')->nullable();
            $table->decimal('annual_amount', 18, 3)->default(0);
            
            $table->decimal('jan_amount', 18, 3)->default(0);
            $table->decimal('feb_amount', 18, 3)->default(0);
            $table->decimal('mar_amount', 18, 3)->default(0);
            $table->decimal('apr_amount', 18, 3)->default(0);
            $table->decimal('may_amount', 18, 3)->default(0);
            $table->decimal('jun_amount', 18, 3)->default(0);
            $table->decimal('jul_amount', 18, 3)->default(0);
            $table->decimal('aug_amount', 18, 3)->default(0);
            $table->decimal('sep_amount', 18, 3)->default(0);
            $table->decimal('oct_amount', 18, 3)->default(0);
            $table->decimal('nov_amount', 18, 3)->default(0);
            $table->decimal('dec_amount', 18, 3)->default(0);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('payment_vouchers');
        Schema::dropIfExists('receipt_vouchers');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('journal_voucher_lines');
        Schema::dropIfExists('journal_vouchers');
        Schema::dropIfExists('cost_centers');
        Schema::dropIfExists('chart_of_accounts');
    }
};
