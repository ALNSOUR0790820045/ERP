<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تحسينات سير عمل العطاءات حسب وثيقة العطاءات الحكومية الموحدة
 * 
 * الخطوات الـ 17:
 * 1. رصد المناقصات + موافقة الشراء
 * 2. تجهيز الأوراق
 * 3. شراء العطاء
 * 4. إدخال وثائق العطاء
 * 5. زيارة المشروع
 * 6. تسعير المشروع
 * 7. إدخال الملاحق
 * 8. تجهيز العرض المالي/الفني
 * 9. تجهيز الكفالات
 * 10. إغلاق العرض
 * 11. تقديم العرض
 * 12. فتح العروض
 * 13. إدخال النتائج
 * 14. انتظار قرار الإحالة (إذا فائز)
 * 15. إصدار كفالة حسن التنفيذ + سحب كفالة الدخول
 * 16. تحويل العطاء إلى مشروع
 * 17. سحب كفالة الدخول (إذا خاسر)
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. مصادر رصد المناقصات
        Schema::create('tender_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->enum('source_type', [
                'newspaper',           // جريدة
                'website',             // موقع إلكتروني
                'government_portal',   // بوابة حكومية
                'direct_invitation',   // دعوة مباشرة
                'personal_relation',   // علاقة شخصية
                'tender_agency',       // وكالة مناقصات
                'social_media',        // وسائل التواصل
                'exhibition',          // معرض
                'other'                // أخرى
            ]);
            $table->string('url')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_subscription')->default(false);
            $table->decimal('subscription_cost', 10, 2)->nullable();
            $table->string('subscription_period')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. رصد العطاء من المصدر
        Schema::create('tender_discoveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('tenders')->cascadeOnDelete();
            $table->foreignId('source_id')->nullable()->constrained('tender_sources');
            $table->date('discovery_date');
            $table->foreignId('discovered_by')->constrained('users');
            $table->string('source_reference')->nullable(); // رقم المرجع في المصدر
            $table->text('initial_notes')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        // 3. موافقة شراء وثائق العطاء
        Schema::create('tender_purchase_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('tenders')->cascadeOnDelete();
            $table->date('request_date');
            $table->foreignId('requested_by')->constrained('users');
            $table->text('justification')->nullable(); // مبررات الشراء
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->enum('status', [
                'pending',    // بانتظار الموافقة
                'approved',   // موافق
                'rejected',   // مرفوض
                'deferred'    // مؤجل
            ])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        // 4. زيارات الموقع
        Schema::create('tender_site_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('tenders')->cascadeOnDelete();
            $table->datetime('visit_date');
            $table->datetime('visit_end_time')->nullable();
            $table->json('visitors')->nullable(); // أسماء الزائرين
            $table->string('site_location')->nullable();
            $table->decimal('site_area', 15, 2)->nullable();
            $table->text('access_conditions')->nullable(); // ظروف الوصول
            $table->text('terrain_description')->nullable(); // وصف التضاريس
            $table->text('existing_structures')->nullable(); // المنشآت القائمة
            $table->text('utilities_available')->nullable(); // المرافق المتوفرة
            $table->text('nearby_facilities')->nullable(); // المرافق القريبة
            $table->text('potential_issues')->nullable(); // المشاكل المحتملة
            $table->text('weather_conditions')->nullable(); // أحوال الطقس
            $table->json('photos')->nullable(); // صور الموقع
            $table->string('visit_report_path')->nullable();
            $table->enum('site_rating', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->text('recommendations')->nullable();
            $table->boolean('owner_representative_present')->default(false);
            $table->string('owner_representative_name')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // 5. تقييم المشروع (Go/No-Go المفصل)
        Schema::create('tender_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('tenders')->cascadeOnDelete();
            $table->foreignId('decision_id')->nullable()->constrained('tender_decisions');
            
            // معايير التقييم التفصيلية
            // أ. التوافق الاستراتيجي
            $table->tinyInteger('strategic_alignment_score')->nullable(); // 1-5
            $table->text('strategic_alignment_notes')->nullable();
            
            // ب. القدرة الفنية
            $table->tinyInteger('technical_capability_score')->nullable();
            $table->text('technical_capability_notes')->nullable();
            
            // ج. توفر الموارد البشرية
            $table->tinyInteger('human_resources_score')->nullable();
            $table->text('human_resources_notes')->nullable();
            
            // د. توفر المعدات
            $table->tinyInteger('equipment_availability_score')->nullable();
            $table->text('equipment_availability_notes')->nullable();
            
            // هـ. القدرة المالية
            $table->tinyInteger('financial_capacity_score')->nullable();
            $table->text('financial_capacity_notes')->nullable();
            
            // و. الخبرة في مشاريع مماثلة
            $table->tinyInteger('similar_experience_score')->nullable();
            $table->text('similar_experience_notes')->nullable();
            
            // ز. العلاقة مع المالك
            $table->tinyInteger('owner_relationship_score')->nullable();
            $table->text('owner_relationship_notes')->nullable();
            
            // ح. المنافسة المتوقعة
            $table->tinyInteger('competition_level_score')->nullable();
            $table->text('competition_level_notes')->nullable();
            
            // ط. هامش الربح المتوقع
            $table->tinyInteger('profit_margin_score')->nullable();
            $table->decimal('expected_profit_percentage', 5, 2)->nullable();
            
            // ي. المخاطر
            $table->tinyInteger('risk_level_score')->nullable();
            $table->text('risk_level_notes')->nullable();
            
            // ك. الموقع والوصول
            $table->tinyInteger('location_score')->nullable();
            $table->text('location_notes')->nullable();
            
            // ل. الجدول الزمني
            $table->tinyInteger('timeline_feasibility_score')->nullable();
            $table->text('timeline_notes')->nullable();
            
            // النتيجة الإجمالية
            $table->decimal('total_weighted_score', 5, 2)->nullable();
            $table->decimal('passing_threshold', 5, 2)->default(60);
            
            $table->enum('recommendation', [
                'strongly_go',    // مشاركة بقوة
                'go',             // مشاركة
                'conditional_go', // مشاركة مشروطة
                'no_go',          // عدم مشاركة
                'defer'           // تأجيل القرار
            ])->nullable();
            
            $table->text('conditions')->nullable(); // الشروط إن وجدت
            $table->foreignId('evaluated_by')->nullable()->constrained('users');
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();
        });

        // 6. تجديد/تمديد الكفالات
        Schema::create('tender_bond_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bond_id')->constrained('tender_bonds')->cascadeOnDelete();
            $table->integer('renewal_number');
            $table->date('request_date');
            $table->date('current_expiry_date');
            $table->date('new_expiry_date');
            $table->integer('extension_days');
            $table->text('reason');
            $table->decimal('renewal_fee', 12, 2)->nullable();
            $table->decimal('commission_amount', 12, 2)->nullable();
            $table->string('new_bond_number')->nullable();
            $table->string('document_path')->nullable();
            $table->enum('status', [
                'pending',    // بانتظار التجديد
                'processing', // جاري المعالجة
                'renewed',    // تم التجديد
                'rejected'    // مرفوض
            ])->default('pending');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 7. سحب الكفالات
        Schema::create('tender_bond_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bond_id')->constrained('tender_bonds')->cascadeOnDelete();
            $table->enum('withdrawal_reason', [
                'tender_won',        // فوز بالعطاء (استبدال بكفالة تنفيذ)
                'tender_lost',       // خسارة العطاء
                'tender_cancelled',  // إلغاء العطاء
                'expired',           // انتهاء الصلاحية
                'replaced',          // استبدال بكفالة جديدة
                'released',          // إفراج من المالك
                'other'              // أخرى
            ]);
            $table->date('request_date');
            $table->date('withdrawal_date')->nullable();
            $table->string('release_letter_number')->nullable();
            $table->date('release_letter_date')->nullable();
            $table->string('release_letter_path')->nullable();
            $table->string('original_bond_path')->nullable();
            $table->enum('status', [
                'pending',      // بانتظار السحب
                'in_progress',  // جاري السحب
                'withdrawn',    // تم السحب
                'returned'      // تم الإرجاع للبنك
            ])->default('pending');
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->date('refund_date')->nullable();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 8. إغلاق العرض (قبل التقديم)
        Schema::create('tender_proposal_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('tenders')->cascadeOnDelete();
            $table->datetime('closure_datetime');
            
            // التحقق من اكتمال المظروف الفني
            $table->boolean('technical_complete')->default(false);
            $table->json('technical_checklist')->nullable();
            $table->text('technical_missing_items')->nullable();
            
            // التحقق من اكتمال المظروف المالي
            $table->boolean('financial_complete')->default(false);
            $table->json('financial_checklist')->nullable();
            $table->text('financial_missing_items')->nullable();
            
            // التحقق من الكفالات
            $table->boolean('bonds_ready')->default(false);
            $table->text('bonds_notes')->nullable();
            
            // التحقق من الوثائق الإدارية
            $table->boolean('admin_docs_complete')->default(false);
            $table->json('admin_docs_checklist')->nullable();
            
            // المراجعة النهائية
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            
            // الموافقة على التقديم
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            
            // السعر النهائي
            $table->decimal('final_price', 18, 3)->nullable();
            $table->text('price_justification')->nullable();
            
            $table->enum('status', [
                'draft',       // مسودة
                'reviewing',   // قيد المراجعة
                'approved',    // موافق للتقديم
                'rejected'     // مرفوض
            ])->default('draft');
            
            $table->timestamps();
        });

        // 9. تتبع قرار الإحالة
        Schema::create('tender_award_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('tenders')->cascadeOnDelete();
            $table->foreignId('award_decision_id')->nullable()->constrained('tender_award_decisions');
            
            // حالة الانتظار
            $table->enum('tracking_status', [
                'awaiting_technical_evaluation',  // انتظار التقييم الفني
                'technical_evaluation_complete',  // اكتمل التقييم الفني
                'awaiting_financial_opening',     // انتظار فتح المالي
                'financial_opening_complete',     // اكتمل فتح المالي
                'awaiting_committee_decision',    // انتظار قرار اللجنة
                'preliminary_award',              // إحالة مبدئية
                'objection_period',               // فترة الاعتراض
                'final_award',                    // إحالة نهائية
                'award_cancelled'                 // إلغاء الإحالة
            ]);
            
            $table->date('status_date');
            $table->text('status_notes')->nullable();
            $table->string('reference_document')->nullable();
            $table->string('document_path')->nullable();
            
            // تفاصيل الإحالة المبدئية
            $table->date('preliminary_award_date')->nullable();
            $table->decimal('preliminary_award_amount', 18, 3)->nullable();
            
            // فترة الاعتراض
            $table->date('objection_period_start')->nullable();
            $table->date('objection_period_end')->nullable();
            $table->boolean('objections_filed')->default(false);
            $table->text('objection_details')->nullable();
            
            // الإحالة النهائية
            $table->date('final_award_date')->nullable();
            $table->decimal('final_award_amount', 18, 3)->nullable();
            $table->string('award_letter_number')->nullable();
            $table->string('award_letter_path')->nullable();
            
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // 10. تحويل العطاء إلى مشروع
        Schema::create('tender_to_project_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('tenders')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects');
            $table->foreignId('contract_id')->nullable()->constrained('contracts');
            
            $table->date('conversion_date');
            $table->foreignId('converted_by')->constrained('users');
            
            // بيانات المشروع المنشأ
            $table->string('project_code')->nullable();
            $table->string('project_name_ar')->nullable();
            $table->string('project_name_en')->nullable();
            
            // بيانات العقد
            $table->string('contract_number')->nullable();
            $table->date('contract_date')->nullable();
            $table->decimal('contract_value', 18, 3)->nullable();
            $table->integer('contract_duration_days')->nullable();
            $table->date('expected_start_date')->nullable();
            $table->date('expected_end_date')->nullable();
            
            // كفالة حسن التنفيذ
            $table->foreignId('performance_bond_id')->nullable()->constrained('tender_bonds');
            $table->decimal('performance_bond_amount', 18, 3)->nullable();
            $table->date('performance_bond_date')->nullable();
            
            // الدفعة المقدمة
            $table->decimal('advance_payment_amount', 18, 3)->nullable();
            $table->decimal('advance_payment_percentage', 5, 2)->nullable();
            $table->foreignId('advance_payment_bond_id')->nullable()->constrained('tender_bonds');
            
            $table->enum('status', [
                'pending',           // بانتظار التحويل
                'contract_signing',  // توقيع العقد
                'project_setup',     // إعداد المشروع
                'completed',         // مكتمل
                'cancelled'          // ملغي
            ])->default('pending');
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 11. سجل مراحل العطاء
        Schema::create('tender_stage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('tenders')->cascadeOnDelete();
            
            $table->enum('stage', [
                'discovery',              // 1. رصد المناقصات
                'purchase_approval',      // 2. موافقة الشراء
                'documents_purchase',     // 3. شراء الوثائق
                'documents_entry',        // 4. إدخال الوثائق
                'site_visit',             // 5. زيارة الموقع
                'evaluation',             // 6. تقييم المشروع
                'go_no_go_decision',      // 7. قرار المشاركة
                'pricing',                // 8. التسعير
                'addenda_entry',          // 9. إدخال الملاحق
                'technical_preparation',  // 10. تجهيز العرض الفني
                'financial_preparation',  // 11. تجهيز العرض المالي
                'bonds_preparation',      // 12. تجهيز الكفالات
                'proposal_closure',       // 13. إغلاق العرض
                'submission',             // 14. تقديم العرض
                'technical_opening',      // 15. فتح الفني
                'financial_opening',      // 16. فتح المالي
                'results_entry',          // 17. إدخال النتائج
                'award_waiting',          // 18. انتظار الإحالة
                'performance_bond',       // 19. كفالة التنفيذ
                'bid_bond_withdrawal',    // 20. سحب كفالة الدخول
                'project_conversion',     // 21. تحويل لمشروع
                'archived'                // 22. أرشفة
            ]);
            
            $table->enum('status', [
                'not_started',   // لم يبدأ
                'in_progress',   // جاري
                'completed',     // مكتمل
                'skipped',       // تم تخطيه
                'failed'         // فشل
            ])->default('not_started');
            
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->integer('stage_order');
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();
            
            $table->unique(['tender_id', 'stage']);
        });

        // 12. تنبيهات وتذكيرات العطاءات
        Schema::create('tender_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('tenders')->cascadeOnDelete();
            
            $table->enum('alert_type', [
                'deadline_approaching',     // موعد نهائي يقترب
                'bond_expiry',              // انتهاء كفالة
                'site_visit_reminder',      // تذكير بزيارة الموقع
                'questions_deadline',       // موعد الاستفسارات
                'decision_required',        // مطلوب قرار
                'submission_reminder',      // تذكير بالتقديم
                'opening_notification',     // إشعار فتح المظاريف
                'award_tracking',           // متابعة الإحالة
                'bond_renewal_needed',      // تجديد كفالة مطلوب
                'document_missing',         // وثيقة ناقصة
                'action_required'           // إجراء مطلوب
            ]);
            
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->text('message_ar')->nullable();
            $table->text('message_en')->nullable();
            
            $table->datetime('alert_date');
            $table->datetime('due_date')->nullable();
            $table->integer('days_before')->nullable();
            
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'sent', 'read', 'dismissed'])->default('pending');
            
            $table->json('recipients')->nullable();
            $table->boolean('email_sent')->default(false);
            $table->boolean('sms_sent')->default(false);
            $table->boolean('system_notification')->default(true);
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // 13. إضافة أعمدة جديدة لجدول العطاءات
        Schema::table('tenders', function (Blueprint $table) {
            // مصدر العطاء
            if (!Schema::hasColumn('tenders', 'source_id')) {
                $table->foreignId('source_id')->nullable()->after('tender_number')->constrained('tender_sources');
            }
            
            // المرحلة الحالية
            if (!Schema::hasColumn('tenders', 'current_stage')) {
                $table->string('current_stage')->nullable()->after('status');
            }
            
            // نسبة الإنجاز
            if (!Schema::hasColumn('tenders', 'completion_percentage')) {
                $table->decimal('completion_percentage', 5, 2)->default(0)->after('current_stage');
            }
            
            // موافقة الشراء
            if (!Schema::hasColumn('tenders', 'purchase_approved')) {
                $table->boolean('purchase_approved')->default(false)->after('documents_price');
            }
            
            // زيارة الموقع
            if (!Schema::hasColumn('tenders', 'site_visited')) {
                $table->boolean('site_visited')->default(false)->after('site_visit_date');
            }
            
            // إغلاق العرض
            if (!Schema::hasColumn('tenders', 'proposal_closed')) {
                $table->boolean('proposal_closed')->default(false)->after('submitted_price');
            }
            
            // تحويل لمشروع
            if (!Schema::hasColumn('tenders', 'converted_to_project')) {
                $table->boolean('converted_to_project')->default(false)->after('contract_id');
            }
            
            // Project ID إذا تم التحويل
            if (!Schema::hasColumn('tenders', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('converted_to_project');
            }
        });

        // 14. تحديث جدول الكفالات
        Schema::table('tender_bonds', function (Blueprint $table) {
            if (!Schema::hasColumn('tender_bonds', 'is_bid_bond')) {
                $table->boolean('is_bid_bond')->default(false)->after('bond_type');
            }
            if (!Schema::hasColumn('tender_bonds', 'is_performance_bond')) {
                $table->boolean('is_performance_bond')->default(false)->after('is_bid_bond');
            }
            if (!Schema::hasColumn('tender_bonds', 'is_advance_payment_bond')) {
                $table->boolean('is_advance_payment_bond')->default(false)->after('is_performance_bond');
            }
            if (!Schema::hasColumn('tender_bonds', 'is_withdrawn')) {
                $table->boolean('is_withdrawn')->default(false)->after('status');
            }
            if (!Schema::hasColumn('tender_bonds', 'withdrawn_at')) {
                $table->timestamp('withdrawn_at')->nullable()->after('is_withdrawn');
            }
            if (!Schema::hasColumn('tender_bonds', 'renewal_count')) {
                $table->integer('renewal_count')->default(0)->after('extension_count');
            }
        });
    }

    public function down(): void
    {
        // حذف الأعمدة المضافة
        Schema::table('tender_bonds', function (Blueprint $table) {
            $columns = ['is_bid_bond', 'is_performance_bond', 'is_advance_payment_bond', 
                       'is_withdrawn', 'withdrawn_at', 'renewal_count'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('tender_bonds', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('tenders', function (Blueprint $table) {
            $columns = ['source_id', 'current_stage', 'completion_percentage', 
                       'purchase_approved', 'site_visited', 'proposal_closed', 
                       'converted_to_project', 'project_id'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('tenders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        // حذف الجداول
        Schema::dropIfExists('tender_alerts');
        Schema::dropIfExists('tender_stage_logs');
        Schema::dropIfExists('tender_to_project_conversions');
        Schema::dropIfExists('tender_award_tracking');
        Schema::dropIfExists('tender_proposal_closures');
        Schema::dropIfExists('tender_bond_withdrawals');
        Schema::dropIfExists('tender_bond_renewals');
        Schema::dropIfExists('tender_evaluations');
        Schema::dropIfExists('tender_site_visits');
        Schema::dropIfExists('tender_purchase_approvals');
        Schema::dropIfExists('tender_discoveries');
        Schema::dropIfExists('tender_sources');
    }
};
