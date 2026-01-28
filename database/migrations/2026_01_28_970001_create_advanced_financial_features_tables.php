<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Additional Advanced Financial Features - Oracle Financials Parity
     */
    public function up(): void
    {
        // =====================================================
        // 1. REVENUE RECOGNITION - Additional Tables
        // =====================================================

        // Revenue Recognition Schedule - جدول الاعتراف بالإيرادات
        if (!Schema::hasTable('revenue_recognition_schedules')) {
            Schema::create('revenue_recognition_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('performance_obligation_id')->constrained()->onDelete('cascade');
                $table->foreignId('fiscal_period_id')->nullable()->constrained('fiscal_periods');
                $table->date('recognition_date');
                $table->decimal('amount', 18, 2);
                $table->decimal('cumulative_recognized', 18, 2)->default(0);
                $table->string('recognition_basis')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('journal_voucher_id')->nullable()->constrained('journal_vouchers');
                $table->enum('status', ['scheduled', 'recognized', 'reversed'])->default('scheduled');
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->timestamps();
            });
        }

        // Variable Consideration - المقابل المتغير
        if (!Schema::hasTable('variable_considerations')) {
            Schema::create('variable_considerations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('revenue_contract_id')->constrained()->onDelete('cascade');
                $table->enum('consideration_type', ['discount', 'rebate', 'refund', 'bonus', 'penalty', 'price_concession']);
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('estimation_method', ['expected_value', 'most_likely']);
                $table->decimal('estimated_amount', 18, 2);
                $table->decimal('constraint_amount', 18, 2)->nullable();
                $table->decimal('actual_amount', 18, 2)->nullable();
                $table->date('resolution_date')->nullable();
                $table->enum('status', ['estimated', 'resolved'])->default('estimated');
                $table->timestamps();
            });
        }

        // =====================================================
        // 2. LEASE ACCOUNTING (IFRS 16) - Full Tables
        // =====================================================

        // Leases - عقود الإيجار
        if (!Schema::hasTable('leases')) {
            Schema::create('leases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained();
                $table->string('lease_number')->unique();
                $table->string('lease_name');
                $table->enum('lease_type', ['finance', 'operating', 'short_term', 'low_value']);
                $table->enum('asset_type', ['building', 'vehicle', 'equipment', 'land', 'other']);
                $table->text('asset_description')->nullable();
                $table->foreignId('lessor_id')->nullable()->constrained('suppliers');
                $table->date('commencement_date');
                $table->date('end_date');
                $table->integer('lease_term_months');
                $table->decimal('monthly_payment', 18, 2);
                $table->enum('payment_frequency', ['monthly', 'quarterly', 'semi_annual', 'annual'])->default('monthly');
                $table->enum('payment_timing', ['beginning', 'end'])->default('end');
                $table->decimal('incremental_borrowing_rate', 8, 4);
                $table->decimal('initial_direct_costs', 18, 2)->default(0);
                $table->decimal('lease_incentives', 18, 2)->default(0);
                $table->decimal('restoration_costs', 18, 2)->default(0);
                $table->decimal('right_of_use_asset', 18, 2)->default(0);
                $table->decimal('lease_liability', 18, 2)->default(0);
                $table->decimal('accumulated_depreciation', 18, 2)->default(0);
                $table->boolean('has_purchase_option')->default(false);
                $table->decimal('purchase_option_price', 18, 2)->nullable();
                $table->boolean('has_extension_option')->default(false);
                $table->integer('extension_period_months')->nullable();
                $table->boolean('has_termination_option')->default(false);
                $table->enum('status', ['draft', 'active', 'modified', 'terminated', 'expired'])->default('draft');
                $table->foreignId('rou_asset_account_id')->nullable()->constrained('chart_of_accounts');
                $table->foreignId('lease_liability_account_id')->nullable()->constrained('chart_of_accounts');
                $table->foreignId('depreciation_expense_account_id')->nullable()->constrained('chart_of_accounts');
                $table->foreignId('interest_expense_account_id')->nullable()->constrained('chart_of_accounts');
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // ROU Asset Depreciation - إهلاك حق استخدام الأصل
        if (!Schema::hasTable('lease_depreciations')) {
            Schema::create('lease_depreciations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lease_id')->constrained()->onDelete('cascade');
                $table->foreignId('fiscal_period_id')->nullable()->constrained('fiscal_periods');
                $table->date('depreciation_date');
                $table->decimal('depreciation_amount', 18, 2);
                $table->decimal('accumulated_depreciation', 18, 2);
                $table->decimal('net_book_value', 18, 2);
                $table->foreignId('journal_voucher_id')->nullable()->constrained('journal_vouchers');
                $table->enum('status', ['pending', 'posted'])->default('pending');
                $table->timestamps();
            });
        }

        // Lease Modifications - تعديلات عقود الإيجار
        if (!Schema::hasTable('lease_modifications')) {
            Schema::create('lease_modifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lease_id')->constrained()->onDelete('cascade');
                $table->date('modification_date');
                $table->enum('modification_type', ['term_extension', 'term_reduction', 'payment_change', 'scope_increase', 'scope_decrease']);
                $table->text('description');
                $table->decimal('revised_lease_liability', 18, 2);
                $table->decimal('rou_asset_adjustment', 18, 2);
                $table->decimal('gain_loss', 18, 2)->default(0);
                $table->foreignId('journal_voucher_id')->nullable()->constrained('journal_vouchers');
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->timestamps();
            });
        }

        // =====================================================
        // 3. FINANCIAL CONSOLIDATION - Additional Tables
        // =====================================================

        // Consolidation Runs - عمليات التجميع
        if (!Schema::hasTable('consolidation_runs')) {
            Schema::create('consolidation_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('consolidation_group_id')->constrained();
                $table->foreignId('fiscal_period_id')->constrained('fiscal_periods');
                $table->string('run_number')->unique();
                $table->date('consolidation_date');
                $table->enum('status', ['draft', 'processing', 'completed', 'error'])->default('draft');
                $table->json('exchange_rates_used')->nullable();
                $table->json('elimination_entries')->nullable();
                $table->json('translation_adjustments')->nullable();
                $table->decimal('total_assets', 18, 2)->nullable();
                $table->decimal('total_liabilities', 18, 2)->nullable();
                $table->decimal('total_equity', 18, 2)->nullable();
                $table->decimal('net_income', 18, 2)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->foreignId('approved_by')->nullable()->constrained('users');
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        // Intercompany Transactions - المعاملات بين الشركات
        if (!Schema::hasTable('intercompany_transactions')) {
            Schema::create('intercompany_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('consolidation_run_id')->nullable()->constrained();
                $table->foreignId('from_company_id')->constrained('companies');
                $table->foreignId('to_company_id')->constrained('companies');
                $table->string('transaction_type');
                $table->string('reference_number');
                $table->date('transaction_date');
                $table->decimal('amount', 18, 2);
                $table->string('currency_code', 3);
                $table->decimal('exchange_rate', 12, 6)->default(1);
                $table->decimal('amount_reporting_currency', 18, 2);
                $table->boolean('is_eliminated')->default(false);
                $table->foreignId('elimination_journal_id')->nullable()->constrained('journal_vouchers');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // =====================================================
        // 4. COST ALLOCATIONS - Additional Tables
        // =====================================================

        // Allocation Runs - تنفيذ التوزيع
        if (!Schema::hasTable('allocation_runs')) {
            Schema::create('allocation_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('allocation_rule_id')->constrained();
                $table->foreignId('fiscal_period_id')->constrained('fiscal_periods');
                $table->string('run_number')->unique();
                $table->date('allocation_date');
                $table->decimal('total_allocated', 18, 2)->default(0);
                $table->enum('status', ['draft', 'processing', 'completed', 'reversed'])->default('draft');
                $table->foreignId('journal_voucher_id')->nullable()->constrained('journal_vouchers');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->timestamps();
            });
        }

        // Allocation Details - تفاصيل التوزيع
        if (!Schema::hasTable('allocation_run_details')) {
            Schema::create('allocation_run_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('allocation_run_id')->constrained()->onDelete('cascade');
                $table->foreignId('allocation_target_id')->constrained();
                $table->decimal('basis_amount', 18, 2)->nullable();
                $table->decimal('allocated_amount', 18, 2);
                $table->decimal('allocation_percentage', 8, 4);
                $table->timestamps();
            });
        }

        // =====================================================
        // 5. FINANCIAL STATEMENTS - Templates
        // =====================================================

        if (!Schema::hasTable('financial_statement_templates')) {
            Schema::create('financial_statement_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained();
                $table->string('code')->unique();
                $table->string('name');
                $table->enum('statement_type', ['balance_sheet', 'income_statement', 'cash_flow', 'equity_changes', 'custom']);
                $table->text('description')->nullable();
                $table->json('structure')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // =====================================================
        // 6. CHEQUE MANAGEMENT - إدارة الشيكات
        // =====================================================

        // Cheque Books - دفاتر الشيكات
        if (!Schema::hasTable('cheque_books')) {
            Schema::create('cheque_books', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bank_account_id')->constrained('bank_accounts');
                $table->string('book_number');
                $table->string('series_prefix')->nullable();
                $table->integer('start_number');
                $table->integer('end_number');
                $table->integer('current_number');
                $table->integer('total_cheques');
                $table->integer('used_cheques')->default(0);
                $table->integer('cancelled_cheques')->default(0);
                $table->date('received_date');
                $table->date('expiry_date')->nullable();
                $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->timestamps();
                
                $table->unique(['bank_account_id', 'book_number']);
            });
        }

        // Cheques Issued - الشيكات الصادرة
        if (!Schema::hasTable('cheques_issued')) {
            Schema::create('cheques_issued', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cheque_book_id')->constrained()->onDelete('cascade');
                $table->foreignId('bank_account_id')->constrained('bank_accounts');
                $table->string('cheque_number');
                $table->date('cheque_date');
                $table->date('due_date')->nullable();
                $table->decimal('amount', 18, 2);
                $table->foreignId('currency_id')->nullable()->constrained('currencies');
                $table->string('payee_name');
                $table->enum('payee_type', ['supplier', 'employee', 'other'])->default('supplier');
                $table->foreignId('payee_id')->nullable();
                $table->string('amount_in_words')->nullable();
                $table->text('memo')->nullable();
                $table->string('reference_type')->nullable();
                $table->foreignId('reference_id')->nullable();
                $table->enum('status', ['draft', 'printed', 'issued', 'cleared', 'bounced', 'cancelled', 'stopped'])->default('draft');
                $table->date('cleared_date')->nullable();
                $table->date('bounced_date')->nullable();
                $table->text('bounce_reason')->nullable();
                $table->foreignId('payment_voucher_id')->nullable()->constrained('payment_vouchers');
                $table->foreignId('journal_voucher_id')->nullable()->constrained('journal_vouchers');
                $table->integer('print_count')->default(0);
                $table->timestamp('last_printed_at')->nullable();
                $table->foreignId('printed_by')->nullable()->constrained('users');
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->timestamps();
                $table->softDeletes();
                
                $table->unique(['bank_account_id', 'cheque_number']);
            });
        }

        // Cheques Received - الشيكات المستلمة
        if (!Schema::hasTable('cheques_received')) {
            Schema::create('cheques_received', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained();
                $table->string('cheque_number');
                $table->string('bank_name');
                $table->string('branch_name')->nullable();
                $table->string('drawer_account_number')->nullable();
                $table->date('cheque_date');
                $table->date('due_date')->nullable();
                $table->decimal('amount', 18, 2);
                $table->foreignId('currency_id')->nullable()->constrained('currencies');
                $table->string('drawer_name');
                $table->enum('drawer_type', ['customer', 'other'])->default('customer');
                $table->foreignId('drawer_id')->nullable();
                $table->text('memo')->nullable();
                $table->string('reference_type')->nullable();
                $table->foreignId('reference_id')->nullable();
                $table->enum('status', ['received', 'under_collection', 'deposited', 'collected', 'returned', 'endorsed', 'cancelled'])->default('received');
                $table->foreignId('deposited_to_bank_id')->nullable()->constrained('bank_accounts');
                $table->date('deposit_date')->nullable();
                $table->date('collection_date')->nullable();
                $table->date('return_date')->nullable();
                $table->text('return_reason')->nullable();
                $table->foreignId('endorsed_to')->nullable();
                $table->date('endorsement_date')->nullable();
                $table->foreignId('receipt_voucher_id')->nullable()->constrained('receipt_vouchers');
                $table->foreignId('journal_voucher_id')->nullable()->constrained('journal_vouchers');
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Cheque Print Templates - قوالب طباعة الشيكات
        if (!Schema::hasTable('cheque_print_templates')) {
            Schema::create('cheque_print_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts');
                $table->string('name');
                $table->enum('paper_size', ['A4', 'letter', 'cheque_size', 'custom'])->default('cheque_size');
                $table->decimal('page_width', 8, 2)->nullable();
                $table->decimal('page_height', 8, 2)->nullable();
                $table->json('field_positions')->nullable();
                $table->string('font_family')->default('Arial');
                $table->integer('font_size')->default(12);
                $table->boolean('print_amount_words')->default(true);
                $table->boolean('print_date')->default(true);
                $table->boolean('print_payee')->default(true);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // =====================================================
        // 7. BANK GUARANTEES - Additional Tables
        // =====================================================

        // Guarantee Renewals - تجديدات الكفالات
        if (!Schema::hasTable('guarantee_renewals')) {
            Schema::create('guarantee_renewals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bank_guarantee_id')->constrained()->onDelete('cascade');
                $table->string('renewal_number');
                $table->date('renewal_date');
                $table->date('old_expiry_date');
                $table->date('new_expiry_date');
                $table->decimal('old_amount', 18, 2);
                $table->decimal('new_amount', 18, 2);
                $table->decimal('renewal_fees', 18, 2)->default(0);
                $table->text('notes')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->foreignId('approved_by')->nullable()->constrained('users');
                $table->timestamps();
            });
        }

        // Letters of Credit - خطابات الاعتماد
        if (!Schema::hasTable('letters_of_credit')) {
            Schema::create('letters_of_credit', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained();
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
                $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders');
                $table->string('lc_number')->unique();
                $table->enum('lc_type', ['sight', 'usance', 'deferred', 'red_clause', 'revolving', 'transferable']);
                $table->string('lc_name');
                $table->foreignId('issuing_bank_id')->nullable()->constrained('bank_accounts');
                $table->string('issuing_bank_name');
                $table->string('advising_bank')->nullable();
                $table->string('confirming_bank')->nullable();
                $table->string('beneficiary_name');
                $table->string('beneficiary_bank')->nullable();
                $table->date('issue_date');
                $table->date('expiry_date');
                $table->date('latest_shipment_date')->nullable();
                $table->decimal('lc_amount', 18, 2);
                $table->foreignId('currency_id')->nullable()->constrained('currencies');
                $table->decimal('tolerance_percentage', 5, 2)->default(0);
                $table->boolean('is_confirmed')->default(false);
                $table->boolean('is_transferable')->default(false);
                $table->boolean('partial_shipment_allowed')->default(true);
                $table->boolean('transhipment_allowed')->default(true);
                $table->text('goods_description')->nullable();
                $table->string('port_of_loading')->nullable();
                $table->string('port_of_discharge')->nullable();
                $table->string('incoterms')->nullable();
                $table->json('required_documents')->nullable();
                $table->decimal('margin_amount', 18, 2)->default(0);
                $table->decimal('commission_amount', 18, 2)->default(0);
                $table->decimal('utilized_amount', 18, 2)->default(0);
                $table->decimal('available_amount', 18, 2)->default(0);
                $table->enum('status', ['draft', 'requested', 'issued', 'amended', 'utilized', 'closed', 'cancelled'])->default('draft');
                $table->text('terms_and_conditions')->nullable();
                $table->string('document_path')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->foreignId('approved_by')->nullable()->constrained('users');
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // LC Amendments - تعديلات خطابات الاعتماد
        if (!Schema::hasTable('lc_amendments')) {
            Schema::create('lc_amendments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('letter_of_credit_id')->constrained()->onDelete('cascade');
                $table->string('amendment_number');
                $table->date('amendment_date');
                $table->enum('amendment_type', ['amount', 'expiry', 'shipment_date', 'terms', 'documents', 'other']);
                $table->text('description');
                $table->decimal('amount_change', 18, 2)->nullable();
                $table->date('new_expiry_date')->nullable();
                $table->date('new_shipment_date')->nullable();
                $table->decimal('amendment_fees', 18, 2)->default(0);
                $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->timestamps();
            });
        }

        // LC Utilizations - استخدامات خطابات الاعتماد
        if (!Schema::hasTable('lc_utilizations')) {
            Schema::create('lc_utilizations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('letter_of_credit_id')->constrained()->onDelete('cascade');
                $table->string('utilization_number');
                $table->date('utilization_date');
                $table->decimal('amount', 18, 2);
                $table->string('shipment_reference')->nullable();
                $table->date('shipment_date')->nullable();
                $table->json('documents_presented')->nullable();
                $table->enum('status', ['pending', 'accepted', 'discrepant', 'paid'])->default('pending');
                $table->text('discrepancies')->nullable();
                $table->foreignId('supplier_invoice_id')->nullable()->constrained('supplier_invoices');
                $table->foreignId('journal_voucher_id')->nullable()->constrained('journal_vouchers');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lc_utilizations');
        Schema::dropIfExists('lc_amendments');
        Schema::dropIfExists('letters_of_credit');
        Schema::dropIfExists('guarantee_renewals');
        Schema::dropIfExists('cheque_print_templates');
        Schema::dropIfExists('cheques_received');
        Schema::dropIfExists('cheques_issued');
        Schema::dropIfExists('cheque_books');
        Schema::dropIfExists('financial_statement_templates');
        Schema::dropIfExists('allocation_run_details');
        Schema::dropIfExists('allocation_runs');
        Schema::dropIfExists('intercompany_transactions');
        Schema::dropIfExists('consolidation_runs');
        Schema::dropIfExists('lease_modifications');
        Schema::dropIfExists('lease_depreciations');
        Schema::dropIfExists('leases');
        Schema::dropIfExists('variable_considerations');
        Schema::dropIfExists('revenue_recognition_schedules');
    }
};
