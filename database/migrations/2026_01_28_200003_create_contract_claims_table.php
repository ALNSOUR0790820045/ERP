<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول المطالبات (Claims)
        Schema::create('contract_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            
            $table->string('claim_number', 50);
            $table->string('claim_type', 50); // extension, compensation, disputed, cost_reimbursement
            $table->string('title');
            $table->text('description');
            $table->string('clause_reference')->nullable();
            
            $table->date('event_date');
            $table->date('notice_date')->nullable();
            $table->date('submission_date');
            $table->boolean('notice_compliant')->default(true);
            
            $table->integer('time_claimed_days')->default(0);
            $table->decimal('cost_claimed', 18, 3)->default(0);
            $table->decimal('loss_profit_claimed', 18, 3)->default(0);
            $table->decimal('total_claimed', 18, 3)->default(0);
            
            $table->integer('time_approved_days')->nullable();
            $table->decimal('cost_approved', 18, 3)->nullable();
            $table->decimal('total_approved', 18, 3)->nullable();
            
            $table->string('status', 50)->default('submitted'); // submitted, under_review, partial, approved, rejected, settled
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->date('review_date')->nullable();
            $table->text('review_notes')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->unique(['contract_id', 'claim_number']);
        });

        // مستندات المطالبات
        Schema::create('contract_claim_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('contract_claims')->cascadeOnDelete();
            
            $table->string('document_type', 50);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->date('document_date')->nullable();
            
            $table->timestamps();
        });

        // جدول التأمينات
        Schema::create('contract_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            
            $table->string('insurance_type', 50); // car, third_party, professional, workmen
            $table->string('policy_number')->nullable();
            $table->string('insurer');
            $table->decimal('coverage_amount', 18, 3);
            $table->decimal('premium', 18, 3)->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies');
            
            $table->date('start_date');
            $table->date('expiry_date');
            $table->decimal('deductible', 18, 3)->nullable();
            
            $table->string('status', 50)->default('active');
            $table->text('coverage_details')->nullable();
            $table->string('document_path')->nullable();
            
            $table->timestamps();
        });

        // جدول العلاقات التعاقدية (المقاولين الفرعيين)
        Schema::create('contract_subcontracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subcontractor_id')->nullable();
            
            $table->string('subcontract_number', 50);
            $table->string('subcontractor_name');
            $table->text('scope_of_work');
            
            $table->decimal('value', 18, 3);
            $table->foreignId('currency_id')->nullable()->constrained('currencies');
            
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('retention_percentage', 5, 2)->default(10);
            $table->integer('payment_terms_days')->default(30);
            
            $table->string('status', 50)->default('active');
            
            $table->timestamps();
        });

        // بنود العقد الفرعي
        Schema::create('contract_subcontract_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcontract_id')->constrained('contract_subcontracts')->cascadeOnDelete();
            $table->foreignId('contract_item_id')->nullable()->constrained('contract_items');
            
            $table->string('item_number', 50);
            $table->text('description');
            $table->foreignId('unit_id')->nullable()->constrained('units');
            
            $table->decimal('quantity', 18, 6)->default(0);
            $table->decimal('unit_rate', 18, 3)->default(0);
            $table->decimal('total_amount', 18, 3)->default(0);
            
            $table->timestamps();
        });

        // جدول مستندات العقد
        Schema::create('contract_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            
            $table->string('document_type', 50);
            $table->string('document_number')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->integer('file_size')->nullable();
            $table->string('file_type', 20)->nullable();
            
            $table->date('document_date')->nullable();
            $table->string('revision', 10)->nullable();
            $table->integer('priority_order')->default(0);
            
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_documents');
        Schema::dropIfExists('contract_subcontract_items');
        Schema::dropIfExists('contract_subcontracts');
        Schema::dropIfExists('contract_insurances');
        Schema::dropIfExists('contract_claim_documents');
        Schema::dropIfExists('contract_claims');
    }
};
