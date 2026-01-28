<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // سجل العملاء
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code', 50)->unique();
            $table->string('company_name');
            $table->string('company_name_en')->nullable();
            $table->enum('customer_type', ['government', 'semi_government', 'private', 'international', 'individual'])->default('private');
            $table->string('industry')->nullable();
            $table->enum('classification', ['vip', 'a', 'b', 'c'])->default('c');
            $table->string('tax_number')->nullable();
            $table->string('commercial_reg')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('الأردن');
            $table->string('postal_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('fax')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->decimal('credit_limit', 18, 2)->default(0);
            $table->integer('payment_terms_days')->default(30);
            $table->string('currency', 10)->default('JOD');
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('iban')->nullable();
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // جهات الاتصال
        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('name');
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_decision_maker')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // فرص المبيعات
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('opportunity_number', 50)->unique();
            $table->string('opportunity_name');
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('customer_contacts')->nullOnDelete();
            $table->enum('source', ['tender', 'referral', 'website', 'cold_call', 'exhibition', 'other'])->nullable();
            $table->enum('type', ['tender', 'direct', 'partnership', 'subcontract'])->default('direct');
            $table->decimal('estimated_value', 18, 2)->default(0);
            $table->integer('probability')->default(50); // %
            $table->date('expected_close_date')->nullable();
            $table->enum('stage', ['identification', 'qualification', 'proposal', 'negotiation', 'won', 'lost'])->default('identification');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('lost_reason')->nullable();
            $table->foreignId('tender_id')->nullable()->constrained('tenders')->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // أنشطة المتابعة
        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->enum('activity_type', ['call', 'meeting', 'email', 'visit', 'task', 'note']);
            $table->string('subject');
            $table->text('description')->nullable();
            $table->morphs('related'); // customer, opportunity, etc.
            $table->dateTime('activity_date');
            $table->dateTime('due_date')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->enum('status', ['planned', 'completed', 'cancelled'])->default('planned');
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            $table->text('outcome')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // تقييمات العملاء
        Schema::create('customer_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('evaluation_date');
            $table->integer('year');
            $table->integer('quarter')->nullable();
            $table->decimal('payment_score', 5, 2)->default(0); // 0-100
            $table->decimal('communication_score', 5, 2)->default(0);
            $table->decimal('repeat_business_score', 5, 2)->default(0);
            $table->decimal('overall_score', 5, 2)->default(0);
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->text('recommendations')->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // العروض المقدمة
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number', 50)->unique();
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained('opportunities')->nullOnDelete();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->string('currency', 10)->default('JOD');
            $table->text('terms_conditions')->nullable();
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // بنود العروض
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->integer('item_number');
            $table->text('description');
            $table->string('unit')->nullable();
            $table->decimal('quantity', 18, 4)->default(1);
            $table->decimal('unit_price', 18, 4)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('total_price', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // حملات تسويقية
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_code', 50)->unique();
            $table->string('name');
            $table->enum('type', ['email', 'social_media', 'event', 'advertising', 'referral', 'other']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('budget', 18, 2)->default(0);
            $table->decimal('actual_cost', 18, 2)->default(0);
            $table->integer('target_leads')->default(0);
            $table->integer('actual_leads')->default(0);
            $table->enum('status', ['planned', 'active', 'completed', 'cancelled'])->default('planned');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // ملاحظات العملاء
        Schema::create('customer_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('content');
            $table->boolean('is_important')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notes');
        Schema::dropIfExists('marketing_campaigns');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('customer_evaluations');
        Schema::dropIfExists('crm_activities');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('customer_contacts');
        Schema::dropIfExists('customers');
    }
};
