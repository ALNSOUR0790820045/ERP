<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==========================================
        // 📋 LEAD MANAGEMENT
        // ==========================================

        // مصادر العملاء المحتملين
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->enum('category', ['online', 'offline', 'referral', 'partner', 'campaign', 'other']);
            $table->text('description')->nullable();
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->decimal('cost_per_lead', 18, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // العملاء المحتملين
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_number', 50)->unique();
            $table->string('company_name')->nullable();
            $table->string('contact_name');
            $table->string('contact_title')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('website')->nullable();
            $table->string('industry')->nullable();
            $table->string('company_size')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('الأردن');
            $table->foreignId('source_id')->nullable()->constrained('lead_sources');
            $table->string('campaign_id')->nullable();
            $table->enum('status', ['new', 'contacted', 'qualified', 'unqualified', 'converted', 'lost'])->default('new');
            $table->enum('rating', ['hot', 'warm', 'cold'])->default('cold');
            $table->integer('score')->default(0);
            $table->decimal('estimated_value', 18, 2)->nullable();
            $table->text('requirements')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamp('first_contact_at')->nullable();
            $table->timestamp('last_contact_at')->nullable();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('converted_customer_id')->nullable()->constrained('customers');
            $table->foreignId('converted_opportunity_id')->nullable()->constrained('opportunities');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'rating']);
            $table->index(['assigned_to', 'status']);
        });

        // قواعد تسجيل نقاط العملاء
        Schema::create('lead_scoring_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->enum('category', ['demographic', 'behavioral', 'engagement', 'fit']);
            $table->string('field_name');
            $table->string('operator');
            $table->string('field_value');
            $table->integer('points');
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // توزيع العملاء المحتملين
        Schema::create('lead_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('assigned_to')->constrained('users');
            $table->foreignId('assigned_by')->constrained('users');
            $table->timestamp('assigned_at');
            $table->string('assignment_reason')->nullable();
            $table->enum('status', ['active', 'reassigned', 'completed'])->default('active');
            $table->timestamps();
        });

        // تحويل العملاء المحتملين
        Schema::create('lead_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('opportunity_id')->nullable()->constrained('opportunities');
            $table->foreignId('contact_id')->nullable()->constrained('customer_contacts');
            $table->timestamp('converted_at');
            $table->foreignId('converted_by')->constrained('users');
            $table->decimal('lead_value', 18, 2)->nullable();
            $table->integer('days_to_convert')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ==========================================
        // 📈 SALES PIPELINE
        // ==========================================

        // مراحل المبيعات
        Schema::create('sales_stages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->integer('sequence');
            $table->integer('default_probability')->default(0);
            $table->string('color', 7)->nullable();
            $table->text('description')->nullable();
            $table->json('required_fields')->nullable();
            $table->boolean('is_won_stage')->default(false);
            $table->boolean('is_lost_stage')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // مسارات المبيعات
        Schema::create('sales_pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->text('description')->nullable();
            $table->enum('pipeline_type', ['standard', 'enterprise', 'government', 'partner']);
            $table->json('stage_ids');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // صفقات المسار
        Schema::create('pipeline_deals', function (Blueprint $table) {
            $table->id();
            $table->string('deal_number', 50)->unique();
            $table->string('deal_name');
            $table->foreignId('pipeline_id')->constrained('sales_pipelines');
            $table->foreignId('stage_id')->constrained('sales_stages');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('opportunity_id')->nullable()->constrained('opportunities');
            $table->foreignId('lead_id')->nullable()->constrained('leads');
            $table->decimal('deal_value', 18, 2);
            $table->string('currency', 3)->default('JOD');
            $table->integer('probability')->default(0);
            $table->decimal('weighted_value', 18, 2)->default(0);
            $table->date('expected_close_date')->nullable();
            $table->date('actual_close_date')->nullable();
            $table->enum('status', ['open', 'won', 'lost', 'on_hold'])->default('open');
            $table->string('lost_reason')->nullable();
            $table->foreignId('competitor_id')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['pipeline_id', 'stage_id', 'status']);
        });

        // توقعات المبيعات
        Schema::create('sales_forecasts', function (Blueprint $table) {
            $table->id();
            $table->string('forecast_code', 50)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('team_id')->nullable();
            $table->integer('year');
            $table->integer('quarter')->nullable();
            $table->integer('month')->nullable();
            $table->decimal('target_amount', 18, 2);
            $table->decimal('pipeline_value', 18, 2)->default(0);
            $table->decimal('weighted_value', 18, 2)->default(0);
            $table->decimal('closed_value', 18, 2)->default(0);
            $table->decimal('gap_amount', 18, 2)->default(0);
            $table->decimal('achievement_percentage', 5, 2)->default(0);
            $table->enum('status', ['draft', 'submitted', 'approved', 'revised'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'year', 'month']);
        });

        // مناطق المبيعات
        Schema::create('sales_territories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->foreignId('parent_id')->nullable()->constrained('sales_territories');
            $table->enum('territory_type', ['region', 'country', 'city', 'district', 'custom']);
            $table->json('geographic_coverage')->nullable();
            $table->json('industry_coverage')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users');
            $table->decimal('target_revenue', 18, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // حصص المبيعات
        Schema::create('sales_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('territory_id')->nullable()->constrained('sales_territories');
            $table->foreignId('team_id')->nullable();
            $table->integer('year');
            $table->integer('quarter')->nullable();
            $table->integer('month')->nullable();
            $table->enum('quota_type', ['revenue', 'deals', 'new_customers', 'units']);
            $table->decimal('quota_amount', 18, 2);
            $table->decimal('achieved_amount', 18, 2)->default(0);
            $table->decimal('achievement_percentage', 5, 2)->default(0);
            $table->enum('status', ['active', 'achieved', 'missed'])->default('active');
            $table->timestamps();
            $table->index(['user_id', 'year', 'month']);
        });

        // ==========================================
        // 🎯 SERVICE MANAGEMENT
        // ==========================================

        // تصنيفات الحالات
        Schema::create('case_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->foreignId('parent_id')->nullable()->constrained('case_categories');
            $table->text('description')->nullable();
            $table->integer('default_priority')->default(3);
            $table->integer('default_sla_hours')->default(24);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // سياسات SLA
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->text('description')->nullable();
            $table->enum('priority', ['critical', 'high', 'medium', 'low']);
            $table->integer('first_response_hours');
            $table->integer('resolution_hours');
            $table->integer('escalation_hours')->nullable();
            $table->json('business_hours')->nullable();
            $table->boolean('exclude_weekends')->default(true);
            $table->boolean('exclude_holidays')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // عقود الخدمة
        Schema::create('service_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->string('contract_name');
            $table->enum('contract_type', ['warranty', 'maintenance', 'support', 'full_service']);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('contract_value', 18, 2);
            $table->string('currency', 3)->default('JOD');
            $table->foreignId('sla_policy_id')->nullable()->constrained('sla_policies');
            $table->integer('included_hours')->nullable();
            $table->integer('used_hours')->default(0);
            $table->integer('included_cases')->nullable();
            $table->integer('used_cases')->default(0);
            $table->json('covered_products')->nullable();
            $table->json('excluded_services')->nullable();
            $table->enum('status', ['draft', 'active', 'expired', 'cancelled', 'renewed'])->default('draft');
            $table->boolean('auto_renew')->default(false);
            $table->integer('renewal_notice_days')->default(30);
            $table->text('terms_conditions')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // حالات الخدمة
        Schema::create('service_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('contact_id')->nullable()->constrained('customer_contacts');
            $table->foreignId('category_id')->nullable()->constrained('case_categories');
            $table->foreignId('service_contract_id')->nullable()->constrained('service_contracts');
            $table->string('subject');
            $table->text('description');
            $table->enum('priority', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->enum('status', ['new', 'assigned', 'in_progress', 'pending_customer', 'resolved', 'closed', 'reopened'])->default('new');
            $table->enum('channel', ['phone', 'email', 'web', 'chat', 'social', 'walk_in'])->default('phone');
            $table->foreignId('sla_policy_id')->nullable()->constrained('sla_policies');
            $table->timestamp('sla_first_response_due')->nullable();
            $table->timestamp('sla_resolution_due')->nullable();
            $table->timestamp('first_responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->boolean('sla_first_response_breached')->default(false);
            $table->boolean('sla_resolution_breached')->default(false);
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('escalated_to')->nullable()->constrained('users');
            $table->integer('escalation_level')->default(0);
            $table->text('resolution')->nullable();
            $table->integer('customer_satisfaction')->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['customer_id', 'status']);
            $table->index(['assigned_to', 'status']);
        });

        // تعليقات الحالات
        Schema::create('case_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('service_cases')->cascadeOnDelete();
            $table->text('comment');
            $table->enum('comment_type', ['internal', 'public', 'system'])->default('internal');
            $table->boolean('is_resolution')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // انتهاكات SLA
        Schema::create('sla_breaches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('service_cases');
            $table->foreignId('sla_policy_id')->constrained('sla_policies');
            $table->enum('breach_type', ['first_response', 'resolution']);
            $table->timestamp('due_at');
            $table->timestamp('breached_at');
            $table->integer('breach_minutes');
            $table->text('reason')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });

        // قاعدة المعرفة
        Schema::create('knowledge_base', function (Blueprint $table) {
            $table->id();
            $table->string('article_number', 50)->unique();
            $table->string('title_ar');
            $table->string('title_en');
            $table->foreignId('category_id')->nullable()->constrained('case_categories');
            $table->text('content_ar');
            $table->text('content_en')->nullable();
            $table->json('keywords')->nullable();
            $table->enum('visibility', ['public', 'internal', 'agents_only'])->default('internal');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->integer('view_count')->default(0);
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ==========================================
        // 👥 CUSTOMER ENGAGEMENT
        // ==========================================

        // شرائح العملاء
        Schema::create('customer_segments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->text('description')->nullable();
            $table->enum('segment_type', ['static', 'dynamic'])->default('static');
            $table->json('criteria')->nullable();
            $table->integer('customer_count')->default(0);
            $table->decimal('total_revenue', 18, 2)->default(0);
            $table->timestamp('last_refreshed_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // عضوية الشرائح
        Schema::create('customer_segment_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segment_id')->constrained('customer_segments')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->timestamp('added_at');
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();
            $table->unique(['segment_id', 'customer_id']);
        });

        // استبيانات العملاء
        Schema::create('customer_surveys', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('title_ar');
            $table->string('title_en');
            $table->text('description')->nullable();
            $table->enum('survey_type', ['satisfaction', 'nps', 'feedback', 'product', 'service']);
            $table->json('questions');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['draft', 'active', 'closed', 'archived'])->default('draft');
            $table->integer('response_count')->default(0);
            $table->decimal('average_score', 5, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // إجابات الاستبيانات
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('customer_surveys')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('contact_id')->nullable()->constrained('customer_contacts');
            $table->string('respondent_email')->nullable();
            $table->json('answers');
            $table->decimal('score', 5, 2)->nullable();
            $table->integer('nps_score')->nullable();
            $table->text('comments')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
        });

        // التغذية الراجعة
        Schema::create('customer_feedback', function (Blueprint $table) {
            $table->id();
            $table->string('feedback_number', 50)->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('contact_id')->nullable()->constrained('customer_contacts');
            $table->enum('feedback_type', ['suggestion', 'compliment', 'complaint', 'inquiry', 'other']);
            $table->enum('channel', ['phone', 'email', 'web', 'social', 'in_person']);
            $table->string('subject');
            $table->text('content');
            $table->enum('sentiment', ['positive', 'neutral', 'negative'])->nullable();
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->enum('status', ['new', 'in_review', 'actioned', 'closed'])->default('new');
            $table->text('response')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('responded_by')->nullable()->constrained('users');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        // الشكاوى
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('contact_id')->nullable()->constrained('customer_contacts');
            $table->foreignId('case_id')->nullable()->constrained('service_cases');
            $table->enum('complaint_type', ['product', 'service', 'billing', 'delivery', 'staff', 'other']);
            $table->enum('severity', ['critical', 'major', 'minor']);
            $table->string('subject');
            $table->text('description');
            $table->json('related_documents')->nullable();
            $table->enum('status', ['open', 'investigating', 'pending_customer', 'resolved', 'escalated', 'closed'])->default('open');
            $table->text('root_cause')->nullable();
            $table->text('corrective_action')->nullable();
            $table->text('preventive_action')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->decimal('compensation_amount', 18, 2)->nullable();
            $table->boolean('customer_satisfied')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // التفاعلات
        Schema::create('customer_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('contact_id')->nullable()->constrained('customer_contacts');
            $table->enum('interaction_type', ['call', 'email', 'meeting', 'visit', 'chat', 'social', 'sms']);
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('subject')->nullable();
            $table->text('summary');
            $table->integer('duration_minutes')->nullable();
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->enum('outcome', ['successful', 'follow_up_needed', 'no_answer', 'voicemail', 'other'])->nullable();
            $table->timestamp('interaction_at');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->index(['customer_id', 'interaction_at']);
        });

        // نقاط الاتصال
        Schema::create('touchpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->enum('touchpoint_type', ['website', 'email', 'social', 'ad', 'event', 'referral', 'sales', 'support']);
            $table->string('channel');
            $table->string('source')->nullable();
            $table->string('campaign')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('touched_at');
            $table->timestamps();
            $table->index(['customer_id', 'touched_at']);
        });

        // ==========================================
        // 💰 SALES COMMISSION
        // ==========================================

        // خطط العمولات
        Schema::create('commission_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->text('description')->nullable();
            $table->enum('plan_type', ['percentage', 'fixed', 'tiered', 'hybrid']);
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->enum('calculation_basis', ['revenue', 'profit', 'units', 'deals']);
            $table->enum('payment_frequency', ['monthly', 'quarterly', 'annually', 'on_payment']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // قواعد العمولات
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('commission_plans')->cascadeOnDelete();
            $table->string('name');
            $table->integer('tier_number')->default(1);
            $table->decimal('min_amount', 18, 2)->default(0);
            $table->decimal('max_amount', 18, 2)->nullable();
            $table->decimal('commission_rate', 8, 4)->nullable();
            $table->decimal('fixed_amount', 18, 2)->nullable();
            $table->json('conditions')->nullable();
            $table->decimal('accelerator_rate', 8, 4)->nullable();
            $table->decimal('accelerator_threshold', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // حسابات العمولات
        Schema::create('commission_calculations', function (Blueprint $table) {
            $table->id();
            $table->string('calculation_number', 50)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('plan_id')->constrained('commission_plans');
            $table->integer('year');
            $table->integer('month');
            $table->decimal('base_amount', 18, 2);
            $table->decimal('commission_amount', 18, 2);
            $table->decimal('accelerator_amount', 18, 2)->default(0);
            $table->decimal('adjustment_amount', 18, 2)->default(0);
            $table->string('adjustment_reason')->nullable();
            $table->decimal('total_commission', 18, 2);
            $table->integer('deals_count')->default(0);
            $table->decimal('quota_achievement', 5, 2)->nullable();
            $table->enum('status', ['calculated', 'approved', 'paid', 'disputed'])->default('calculated');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'year', 'month']);
        });

        // دفعات العمولات
        Schema::create('commission_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 50)->unique();
            $table->foreignId('calculation_id')->constrained('commission_calculations');
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('JOD');
            $table->date('payment_date');
            $table->enum('payment_method', ['bank_transfer', 'check', 'cash', 'payroll']);
            $table->string('reference_number')->nullable();
            $table->enum('status', ['pending', 'processed', 'paid', 'cancelled'])->default('pending');
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ربط المستخدمين بخطط العمولات
        Schema::create('user_commission_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('plan_id')->constrained('commission_plans');
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->decimal('custom_rate', 8, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'plan_id', 'effective_from']);
        });

        // ==========================================
        // 📧 CAMPAIGN ENHANCEMENTS
        // ==========================================

        // تفاصيل الحملات
        Schema::create('campaign_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('marketing_campaigns')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('lead_id')->nullable()->constrained('leads');
            $table->foreignId('segment_id')->nullable()->constrained('customer_segments');
            $table->enum('status', ['pending', 'sent', 'opened', 'clicked', 'converted', 'unsubscribed', 'bounced'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
        });

        // استجابات الحملات
        Schema::create('campaign_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('marketing_campaigns');
            $table->foreignId('target_id')->nullable()->constrained('campaign_targets');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('lead_id')->nullable()->constrained('leads');
            $table->enum('response_type', ['open', 'click', 'reply', 'conversion', 'unsubscribe', 'bounce', 'complaint']);
            $table->string('link_clicked')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('responded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Campaign Enhancements
        Schema::dropIfExists('campaign_responses');
        Schema::dropIfExists('campaign_targets');

        // Commission
        Schema::dropIfExists('user_commission_plans');
        Schema::dropIfExists('commission_payments');
        Schema::dropIfExists('commission_calculations');
        Schema::dropIfExists('commission_rules');
        Schema::dropIfExists('commission_plans');

        // Customer Engagement
        Schema::dropIfExists('touchpoints');
        Schema::dropIfExists('customer_interactions');
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('customer_feedback');
        Schema::dropIfExists('survey_responses');
        Schema::dropIfExists('customer_surveys');
        Schema::dropIfExists('customer_segment_members');
        Schema::dropIfExists('customer_segments');

        // Service Management
        Schema::dropIfExists('knowledge_base');
        Schema::dropIfExists('sla_breaches');
        Schema::dropIfExists('case_comments');
        Schema::dropIfExists('service_cases');
        Schema::dropIfExists('service_contracts');
        Schema::dropIfExists('sla_policies');
        Schema::dropIfExists('case_categories');

        // Sales Pipeline
        Schema::dropIfExists('sales_quotas');
        Schema::dropIfExists('sales_territories');
        Schema::dropIfExists('sales_forecasts');
        Schema::dropIfExists('pipeline_deals');
        Schema::dropIfExists('sales_pipelines');
        Schema::dropIfExists('sales_stages');

        // Lead Management
        Schema::dropIfExists('lead_conversions');
        Schema::dropIfExists('lead_assignments');
        Schema::dropIfExists('lead_scoring_rules');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('lead_sources');
    }
};
