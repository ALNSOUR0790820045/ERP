<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenders', function (Blueprint $table) {
            $table->id();
            
            // التعريف
            $table->string('tender_number', 50)->unique();
            $table->string('reference_number', 100)->nullable();
            
            // الوصف
            $table->string('name_ar', 500);
            $table->string('name_en', 500)->nullable();
            $table->text('description')->nullable();
            
            // التصنيف
            $table->enum('tender_type', ['open', 'limited', 'two_stage', 'prequalification', 'rfq', 'private'])->default('open');
            $table->enum('tender_method', ['public', 'limited', 'direct'])->default('public');
            $table->foreignId('project_type_id')->nullable()->constrained('project_types')->nullOnDelete();
            $table->foreignId('specialization_id')->nullable()->constrained('specializations')->nullOnDelete();
            
            // الجهة المالكة
            $table->enum('owner_type', ['government', 'private', 'international'])->default('government');
            $table->foreignId('owner_id')->nullable()->constrained('owners')->nullOnDelete();
            $table->string('owner_name', 255)->nullable();
            $table->string('owner_contact_person', 255)->nullable();
            $table->string('owner_phone', 50)->nullable();
            $table->string('owner_email', 255)->nullable();
            $table->text('owner_address')->nullable();
            
            // الاستشاري
            $table->foreignId('consultant_id')->nullable()->constrained('consultants')->nullOnDelete();
            $table->string('consultant_name', 255)->nullable();
            
            // الموقع
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->text('site_address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // التواريخ
            $table->date('publication_date')->nullable();
            $table->date('documents_sale_start')->nullable();
            $table->date('documents_sale_end')->nullable();
            $table->datetime('site_visit_date')->nullable();
            $table->datetime('questions_deadline')->nullable();
            $table->datetime('submission_deadline');
            $table->datetime('opening_date')->nullable();
            $table->integer('validity_period')->default(90);
            $table->date('expected_award_date')->nullable();
            
            // القيم
            $table->decimal('estimated_value', 18, 3)->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('documents_price', 10, 2)->nullable();
            
            // الكفالات
            $table->enum('bid_bond_type', ['bank_guarantee', 'certified_check', 'transfer', 'cash'])->nullable();
            $table->decimal('bid_bond_percentage', 5, 2)->nullable();
            $table->decimal('bid_bond_amount', 18, 3)->nullable();
            $table->decimal('performance_bond_percentage', 5, 2)->default(10);
            $table->decimal('advance_payment_percentage', 5, 2)->nullable();
            $table->decimal('retention_percentage', 5, 2)->default(10);
            
            // المتطلبات
            $table->string('required_classification', 100)->nullable();
            $table->integer('minimum_experience_years')->nullable();
            $table->integer('minimum_similar_projects')->nullable();
            $table->decimal('minimum_project_value', 18, 3)->nullable();
            $table->text('financial_requirements')->nullable();
            $table->text('technical_requirements')->nullable();
            $table->text('other_requirements')->nullable();
            
            // التسعير
            $table->decimal('total_direct_cost', 18, 3)->nullable();
            $table->decimal('total_overhead', 18, 3)->nullable();
            $table->decimal('total_cost', 18, 3)->nullable();
            $table->decimal('markup_percentage', 5, 2)->nullable();
            $table->decimal('markup_amount', 18, 3)->nullable();
            $table->decimal('submitted_price', 18, 3)->nullable();
            
            // الحالة
            $table->enum('status', ['new', 'studying', 'go', 'no_go', 'pricing', 'ready', 'submitted', 'opening', 'won', 'lost', 'cancelled'])->default('new');
            $table->enum('decision', ['pending', 'go', 'no_go'])->default('pending');
            $table->date('decision_date')->nullable();
            $table->foreignId('decision_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_notes')->nullable();
            
            // التقديم
            $table->datetime('submission_date')->nullable();
            $table->enum('submission_method', ['hand', 'mail', 'electronic'])->nullable();
            $table->string('submitted_by', 255)->nullable();
            $table->string('receipt_number', 100)->nullable();
            
            // النتيجة
            $table->enum('result', ['pending', 'won', 'lost', 'cancelled'])->default('pending');
            $table->date('award_date')->nullable();
            $table->string('winner_name', 255)->nullable();
            $table->decimal('winning_price', 18, 3)->nullable();
            $table->integer('our_rank')->nullable();
            $table->text('loss_reason')->nullable();
            $table->text('lessons_learned')->nullable();
            
            // العقد المرتبط
            $table->unsignedBigInteger('contract_id')->nullable();
            
            // التدقيق
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // الفهارس
            $table->index('status');
            $table->index('tender_type');
            $table->index('owner_type');
            $table->index('submission_deadline');
            $table->index('result');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenders');
    }
};
