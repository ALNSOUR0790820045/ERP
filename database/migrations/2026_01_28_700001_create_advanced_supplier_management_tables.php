<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Supplier Qualifications - تأهيل الموردين
        if (!Schema::hasTable('supplier_qualifications')) {
            Schema::create('supplier_qualifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->string('qualification_type');
                $table->string('qualification_level')->nullable();
                $table->string('status')->default('pending');
                $table->date('application_date');
                $table->date('qualification_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users');
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->decimal('score', 5, 2)->nullable();
                $table->json('criteria_scores')->nullable();
                $table->json('required_documents')->nullable();
                $table->boolean('is_approved')->default(false);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['supplier_id', 'status']);
            });
        }

        // 2. Supplier Certifications - شهادات الموردين
        if (!Schema::hasTable('supplier_certifications')) {
            Schema::create('supplier_certifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->string('certification_type');
                $table->string('certification_number')->nullable();
                $table->string('certification_name');
                $table->string('issuing_authority');
                $table->string('issuing_country')->nullable();
                $table->date('issue_date');
                $table->date('expiry_date')->nullable();
                $table->string('status')->default('active');
                $table->string('file_path')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->foreignId('verified_by')->nullable()->constrained('users');
                $table->timestamp('verified_at')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['supplier_id', 'certification_type']);
            });
        }

        // 3. Supplier Licenses - تراخيص الموردين
        if (!Schema::hasTable('supplier_licenses')) {
            Schema::create('supplier_licenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->string('license_type');
                $table->string('license_number');
                $table->string('license_name');
                $table->string('issuing_authority');
                $table->string('jurisdiction')->nullable();
                $table->date('issue_date');
                $table->date('expiry_date');
                $table->string('status')->default('active');
                $table->string('file_path')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->foreignId('verified_by')->nullable()->constrained('users');
                $table->integer('renewal_reminder_days')->default(30);
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['supplier_id', 'expiry_date']);
            });
        }

        // 4. Supplier Documents - مستندات الموردين
        if (!Schema::hasTable('supplier_documents')) {
            Schema::create('supplier_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->string('document_type');
                $table->string('document_name');
                $table->string('document_number')->nullable();
                $table->string('file_path');
                $table->string('file_name');
                $table->integer('file_size')->nullable();
                $table->string('mime_type')->nullable();
                $table->date('issue_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('status')->default('active');
                $table->boolean('is_mandatory')->default(false);
                $table->boolean('is_verified')->default(false);
                $table->foreignId('uploaded_by')->nullable()->constrained('users');
                $table->foreignId('verified_by')->nullable()->constrained('users');
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 5. Supplier Portal Users - مستخدمي بوابة الموردين
        if (!Schema::hasTable('supplier_portal_users')) {
            Schema::create('supplier_portal_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('phone')->nullable();
                $table->string('position')->nullable();
                $table->string('role')->default('user');
                $table->boolean('is_primary')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamp('email_verified_at')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->string('last_login_ip')->nullable();
                $table->json('permissions')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // 6. Supplier Notifications - إشعارات الموردين
        if (!Schema::hasTable('supplier_notifications')) {
            Schema::create('supplier_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->foreignId('portal_user_id')->nullable()->constrained('supplier_portal_users');
                $table->string('notification_type');
                $table->string('title');
                $table->text('message');
                $table->string('priority')->default('normal');
                $table->string('action_url')->nullable();
                $table->morphs('notifiable');
                $table->timestamp('read_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->string('sent_via')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['supplier_id', 'read_at']);
            });
        }

        // 7. Supplier Messages - رسائل التواصل مع الموردين
        if (!Schema::hasTable('supplier_messages')) {
            Schema::create('supplier_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->string('subject');
                $table->text('message');
                $table->string('direction')->default('outbound');
                $table->foreignId('sent_by')->nullable()->constrained('users');
                $table->foreignId('portal_user_id')->nullable()->constrained('supplier_portal_users');
                $table->foreignId('parent_id')->nullable()->constrained('supplier_messages');
                $table->morphs('regarding');
                $table->timestamp('read_at')->nullable();
                $table->json('attachments')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 8. Supplier Risks - مخاطر الموردين
        if (!Schema::hasTable('supplier_risks')) {
            Schema::create('supplier_risks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->string('risk_category');
                $table->string('risk_type');
                $table->string('title');
                $table->text('description');
                $table->string('likelihood')->default('medium');
                $table->string('impact')->default('medium');
                $table->string('risk_level')->default('medium');
                $table->decimal('risk_score', 5, 2)->nullable();
                $table->string('status')->default('identified');
                $table->text('mitigation_strategy')->nullable();
                $table->text('contingency_plan')->nullable();
                $table->foreignId('owner_id')->nullable()->constrained('users');
                $table->date('identified_date');
                $table->date('review_date')->nullable();
                $table->date('resolved_date')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 9. Supplier Risk Assessments - تقييمات مخاطر الموردين
        if (!Schema::hasTable('supplier_risk_assessments')) {
            Schema::create('supplier_risk_assessments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->string('assessment_type');
                $table->date('assessment_date');
                $table->foreignId('assessed_by')->constrained('users');
                $table->decimal('financial_risk_score', 5, 2)->nullable();
                $table->decimal('operational_risk_score', 5, 2)->nullable();
                $table->decimal('compliance_risk_score', 5, 2)->nullable();
                $table->decimal('reputation_risk_score', 5, 2)->nullable();
                $table->decimal('overall_risk_score', 5, 2)->nullable();
                $table->string('risk_rating')->nullable();
                $table->json('detailed_scores')->nullable();
                $table->text('findings')->nullable();
                $table->text('recommendations')->nullable();
                $table->date('next_assessment_date')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 10. Supplier Audits - تدقيق الموردين
        if (!Schema::hasTable('supplier_audits')) {
            Schema::create('supplier_audits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->string('audit_number');
                $table->string('audit_type');
                $table->string('audit_scope')->nullable();
                $table->date('planned_date');
                $table->date('actual_date')->nullable();
                $table->foreignId('lead_auditor_id')->nullable()->constrained('users');
                $table->json('audit_team')->nullable();
                $table->string('status')->default('planned');
                $table->text('objective')->nullable();
                $table->json('checklist')->nullable();
                $table->text('findings')->nullable();
                $table->integer('non_conformities_count')->default(0);
                $table->integer('observations_count')->default(0);
                $table->string('overall_rating')->nullable();
                $table->text('conclusion')->nullable();
                $table->date('report_date')->nullable();
                $table->string('report_file')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 11. Supplier Compliance Checks - فحوصات الامتثال
        if (!Schema::hasTable('supplier_compliance_checks')) {
            Schema::create('supplier_compliance_checks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->string('compliance_type');
                $table->string('requirement');
                $table->text('description')->nullable();
                $table->string('status')->default('pending');
                $table->date('check_date');
                $table->foreignId('checked_by')->constrained('users');
                $table->boolean('is_compliant')->nullable();
                $table->text('findings')->nullable();
                $table->text('corrective_action')->nullable();
                $table->date('action_due_date')->nullable();
                $table->date('action_completed_date')->nullable();
                $table->string('evidence_file')->nullable();
                $table->date('next_check_date')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 12. Supplier KPIs - مؤشرات أداء الموردين
        if (!Schema::hasTable('supplier_kpis')) {
            Schema::create('supplier_kpis', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->string('period_type')->default('monthly');
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('on_time_delivery_rate', 5, 2)->nullable();
                $table->decimal('quality_acceptance_rate', 5, 2)->nullable();
                $table->decimal('defect_rate', 5, 2)->nullable();
                $table->decimal('response_time_hours', 8, 2)->nullable();
                $table->decimal('price_competitiveness_score', 5, 2)->nullable();
                $table->decimal('invoice_accuracy_rate', 5, 2)->nullable();
                $table->decimal('overall_score', 5, 2)->nullable();
                $table->integer('total_orders')->default(0);
                $table->integer('on_time_orders')->default(0);
                $table->integer('rejected_orders')->default(0);
                $table->decimal('total_value', 15, 2)->default(0);
                $table->json('detailed_metrics')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['supplier_id', 'period_start']);
            });
        }

        // 13. Blanket Purchase Agreements - اتفاقيات الشراء الإطارية
        if (!Schema::hasTable('blanket_purchase_agreements')) {
            Schema::create('blanket_purchase_agreements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->foreignId('company_id')->nullable()->constrained();
                $table->string('agreement_number');
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('agreement_type')->default('quantity');
                $table->date('start_date');
                $table->date('end_date');
                $table->decimal('agreed_amount', 15, 2)->nullable();
                $table->decimal('minimum_amount', 15, 2)->nullable();
                $table->decimal('released_amount', 15, 2)->default(0);
                $table->integer('agreed_quantity')->nullable();
                $table->integer('released_quantity')->default(0);
                $table->foreignId('currency_id')->nullable()->constrained('currencies');
                $table->string('payment_terms')->nullable();
                $table->text('terms_conditions')->nullable();
                $table->string('status')->default('draft');
                $table->foreignId('approved_by')->nullable()->constrained('users');
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 14. Blanket Agreement Items - بنود الاتفاقيات الإطارية
        if (!Schema::hasTable('blanket_agreement_items')) {
            Schema::create('blanket_agreement_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agreement_id')->constrained('blanket_purchase_agreements')->cascadeOnDelete();
                $table->foreignId('item_id')->nullable()->constrained('items');
                $table->string('item_description');
                $table->string('unit');
                $table->decimal('unit_price', 15, 4);
                $table->integer('agreed_quantity')->nullable();
                $table->integer('released_quantity')->default(0);
                $table->decimal('min_order_qty', 15, 4)->nullable();
                $table->decimal('max_order_qty', 15, 4)->nullable();
                $table->json('price_breaks')->nullable();
                $table->text('specifications')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 15. Blanket Agreement Releases - إصدارات الاتفاقيات الإطارية
        if (!Schema::hasTable('blanket_agreement_releases')) {
            Schema::create('blanket_agreement_releases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agreement_id')->constrained('blanket_purchase_agreements')->cascadeOnDelete();
                $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders');
                $table->string('release_number');
                $table->date('release_date');
                $table->decimal('release_amount', 15, 2);
                $table->integer('release_quantity')->nullable();
                $table->string('status')->default('released');
                $table->foreignId('released_by')->constrained('users');
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 16. Supplier Negotiations - مفاوضات الموردين
        if (!Schema::hasTable('supplier_negotiations')) {
            Schema::create('supplier_negotiations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->morphs('negotiable');
                $table->string('negotiation_number');
                $table->string('subject');
                $table->string('negotiation_type');
                $table->string('status')->default('initiated');
                $table->date('start_date');
                $table->date('target_close_date')->nullable();
                $table->date('actual_close_date')->nullable();
                $table->foreignId('lead_negotiator_id')->nullable()->constrained('users');
                $table->json('team_members')->nullable();
                $table->decimal('initial_value', 15, 2)->nullable();
                $table->decimal('target_value', 15, 2)->nullable();
                $table->decimal('final_value', 15, 2)->nullable();
                $table->decimal('savings_amount', 15, 2)->nullable();
                $table->decimal('savings_percentage', 5, 2)->nullable();
                $table->integer('rounds_count')->default(0);
                $table->text('outcome')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 17. Supplier Price Lists - قوائم أسعار الموردين
        if (!Schema::hasTable('supplier_price_lists')) {
            Schema::create('supplier_price_lists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->string('price_list_name');
                $table->string('price_list_type')->default('standard');
                $table->foreignId('currency_id')->nullable()->constrained('currencies');
                $table->date('effective_date');
                $table->date('expiry_date')->nullable();
                $table->string('status')->default('active');
                $table->boolean('is_default')->default(false);
                $table->text('terms')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 18. Supplier Price List Items - بنود قوائم الأسعار
        if (!Schema::hasTable('supplier_price_list_items')) {
            Schema::create('supplier_price_list_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('price_list_id')->constrained('supplier_price_lists')->cascadeOnDelete();
                $table->foreignId('item_id')->nullable()->constrained('items');
                $table->string('item_code')->nullable();
                $table->string('item_description');
                $table->string('unit');
                $table->decimal('unit_price', 15, 4);
                $table->decimal('min_quantity', 15, 4)->nullable();
                $table->decimal('discount_percentage', 5, 2)->nullable();
                $table->integer('lead_time_days')->nullable();
                $table->json('price_breaks')->nullable();
                $table->boolean('is_active')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 19. Supplier Incidents - حوادث ومشاكل الموردين
        if (!Schema::hasTable('supplier_incidents')) {
            Schema::create('supplier_incidents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->string('incident_number');
                $table->string('incident_type');
                $table->string('severity')->default('medium');
                $table->string('title');
                $table->text('description');
                $table->date('incident_date');
                $table->date('reported_date');
                $table->foreignId('reported_by')->constrained('users');
                $table->morphs('related');
                $table->string('status')->default('open');
                $table->text('root_cause')->nullable();
                $table->text('corrective_action')->nullable();
                $table->text('preventive_action')->nullable();
                $table->date('resolution_date')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users');
                $table->decimal('financial_impact', 15, 2)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // Add columns to suppliers table
        if (Schema::hasTable('suppliers')) {
            if (!Schema::hasColumn('suppliers', 'qualification_status')) {
                Schema::table('suppliers', function (Blueprint $table) {
                    $table->string('qualification_status')->default('pending')->after('is_blacklisted');
                    $table->date('qualification_date')->nullable()->after('qualification_status');
                    $table->date('qualification_expiry')->nullable()->after('qualification_date');
                    $table->string('risk_level')->nullable()->after('qualification_expiry');
                    $table->decimal('risk_score', 5, 2)->nullable()->after('risk_level');
                    $table->boolean('has_portal_access')->default(false)->after('risk_score');
                    $table->string('preferred_communication')->nullable()->after('has_portal_access');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_incidents');
        Schema::dropIfExists('supplier_price_list_items');
        Schema::dropIfExists('supplier_price_lists');
        Schema::dropIfExists('supplier_negotiations');
        Schema::dropIfExists('blanket_agreement_releases');
        Schema::dropIfExists('blanket_agreement_items');
        Schema::dropIfExists('blanket_purchase_agreements');
        Schema::dropIfExists('supplier_kpis');
        Schema::dropIfExists('supplier_compliance_checks');
        Schema::dropIfExists('supplier_audits');
        Schema::dropIfExists('supplier_risk_assessments');
        Schema::dropIfExists('supplier_risks');
        Schema::dropIfExists('supplier_messages');
        Schema::dropIfExists('supplier_notifications');
        Schema::dropIfExists('supplier_portal_users');
        Schema::dropIfExists('supplier_documents');
        Schema::dropIfExists('supplier_licenses');
        Schema::dropIfExists('supplier_certifications');
        Schema::dropIfExists('supplier_qualifications');
    }
};
