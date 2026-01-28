<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تطوير نظام العطاءات بناءً على وثيقة العطاءات الأردنية الموحدة 2024
 * 
 * المراحل المغطاة:
 * 1. الدعوة للمناقصة (Tender Invitation)
 * 2. تعليمات المناقصين (Instructions to Bidders)
 * 3. جدول بيانات المناقصة (Bid Data Sheet)
 * 4. معايير التقييم والتأهيل (Evaluation & Qualification Criteria)
 * 5. نماذج العرض (Bid Forms)
 * 6. الشروط العامة والخاصة (GCC & SCC)
 * 7. نماذج العقد والكفالات (Contract Forms & Bonds)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ========================================
        // المرحلة 1: جداول الدعوة للمناقصة
        // ========================================
        
        // جدول إعلانات العطاءات (Tender Announcements)
        if (!Schema::hasTable('tender_announcements')) {
            Schema::create('tender_announcements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->constrained()->onDelete('cascade');
                
                // بيانات الإعلان
                $table->enum('announcement_type', ['invitation', 'addendum', 'clarification', 'cancellation', 'extension', 'result']);
                $table->string('announcement_number', 50)->nullable();
                $table->date('announcement_date');
                
                // وسائل النشر (حسب الوثيقة: جريدة رسمية، صحف، موقع إلكتروني)
                $table->json('publication_channels')->nullable(); // ['official_gazette', 'al_rai', 'doustour', 'gtd_website', 'ministry_website']
                $table->date('publication_date')->nullable();
                $table->string('publication_reference', 100)->nullable();
                
                // محتوى الإعلان
                $table->string('title_ar', 500);
                $table->string('title_en', 500)->nullable();
                $table->text('content_ar');
                $table->text('content_en')->nullable();
                
                // المرفقات
                $table->string('attachment_path', 500)->nullable();
                
                // الحالة
                $table->enum('status', ['draft', 'published', 'cancelled'])->default('draft');
                $table->timestamp('published_at')->nullable();
                
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->timestamps();
            });
        }

        // ========================================
        // المرحلة 2: جداول تعليمات المناقصين
        // ========================================
        
        // جدول متطلبات الأهلية (Eligibility Requirements) - الفقرات 4.1-4.7
        if (!Schema::hasTable('tender_eligibility_requirements')) {
            Schema::create('tender_eligibility_requirements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->constrained()->onDelete('cascade');
                
                // نوع المتطلب
                $table->enum('requirement_type', [
                    'classification',       // التصنيف المطلوب
                    'experience_years',     // سنوات الخبرة
                    'similar_projects',     // مشاريع مماثلة
                    'financial_capability', // القدرة المالية
                    'technical_capability', // القدرة الفنية
                    'legal_status',         // الوضع القانوني
                    'no_conflict',          // عدم تعارض المصالح
                    'not_blacklisted',      // عدم الحرمان
                    'other'
                ]);
                
                $table->string('description_ar', 500);
                $table->string('description_en', 500)->nullable();
                $table->text('details')->nullable();
                
                // معايير التقييم
                $table->boolean('is_mandatory')->default(true);
                $table->decimal('weight', 5, 2)->nullable(); // للمعايير الموزونة
                $table->decimal('minimum_score', 5, 2)->nullable();
                
                // المستندات المطلوبة
                $table->json('required_documents')->nullable();
                
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // جدول استيفاء متطلبات الأهلية للمناقصين
        if (!Schema::hasTable('tender_bidder_eligibility')) {
            Schema::create('tender_bidder_eligibility', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->constrained()->onDelete('cascade');
                $table->foreignId('competitor_id')->constrained('tender_competitors')->onDelete('cascade');
                $table->foreignId('requirement_id')->constrained('tender_eligibility_requirements')->onDelete('cascade');
                
                // نتيجة التقييم
                $table->boolean('is_met')->nullable();
                $table->decimal('score', 5, 2)->nullable();
                $table->text('notes')->nullable();
                $table->string('document_path', 500)->nullable();
                
                // المقيم
                $table->foreignId('evaluated_by')->nullable()->constrained('users');
                $table->timestamp('evaluated_at')->nullable();
                
                $table->timestamps();
            });
        }

        // ========================================
        // المرحلة 3: جدول بيانات المناقصة (BDS)
        // ========================================
        
        if (!Schema::hasTable('tender_bid_data_sheets')) {
            Schema::create('tender_bid_data_sheets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->constrained()->onDelete('cascade');
                
                // ITB 1.1 - نطاق المناقصة
                $table->string('procuring_entity_ar', 255); // الجهة المشترية
                $table->string('procuring_entity_en', 255)->nullable();
                $table->string('beneficiary_entity_ar', 255)->nullable(); // الجهة المستفيدة
                $table->string('beneficiary_entity_en', 255)->nullable();
                $table->text('works_description_ar'); // وصف الأشغال
                $table->text('works_description_en')->nullable();
                $table->integer('number_of_packages')->default(1); // عدد الحزم
                
                // ITB 2.1 - مصدر التمويل
                $table->enum('funding_source', ['general_budget', 'project_loan', 'grant', 'self_funded', 'mixed']);
                $table->string('funding_details', 500)->nullable();
                $table->string('project_name', 255)->nullable(); // اسم المشروع الممول
                $table->string('lender_name', 255)->nullable(); // الجهة الممولة
                
                // ITB 4.2 - الائتلاف
                $table->boolean('consortium_allowed')->default(false);
                $table->integer('max_consortium_members')->nullable();
                $table->text('consortium_requirements')->nullable();
                
                // ITB 7.1 - التوضيحات
                $table->text('clarification_address')->nullable();
                $table->string('clarification_email', 255)->nullable();
                $table->datetime('clarification_deadline')->nullable();
                
                // ITB 8.1 - زيارة الموقع
                $table->boolean('site_visit_required')->default(false);
                $table->datetime('site_visit_date')->nullable();
                $table->text('site_visit_location')->nullable();
                $table->text('site_visit_instructions')->nullable();
                
                // ITB 11 - لغة العرض
                $table->enum('bid_language', ['arabic', 'english', 'both'])->default('arabic');
                
                // ITB 14 - عملة العرض
                $table->foreignId('bid_currency_id')->nullable()->constrained('currencies');
                $table->boolean('multiple_currencies_allowed')->default(false);
                
                // ITB 18 - فترة سريان العرض
                $table->integer('bid_validity_days')->default(90);
                
                // ITB 19 - تأمين دخول العطاء
                $table->enum('bid_security_type', ['bank_guarantee', 'certified_check', 'either'])->default('either');
                $table->decimal('bid_security_percentage', 5, 2)->nullable();
                $table->decimal('bid_security_amount', 18, 3)->nullable();
                $table->integer('bid_security_validity_days')->nullable();
                $table->text('bid_security_beneficiary')->nullable();
                
                // ITB 22 - تقديم العروض
                $table->datetime('submission_deadline');
                $table->text('submission_address');
                $table->boolean('electronic_submission_allowed')->default(false);
                $table->string('electronic_submission_url', 500)->nullable();
                
                // ITB 25 - فتح العروض
                $table->datetime('opening_date');
                $table->text('opening_location')->nullable();
                $table->boolean('bidders_allowed_at_opening')->default(true);
                
                // ITB 35 - تأمين حسن التنفيذ
                $table->decimal('performance_security_percentage', 5, 2)->default(10);
                $table->integer('performance_security_validity_days')->nullable();
                
                // ITB 32 - الأفضلية للمنشآت الصغيرة
                $table->boolean('sme_preference_applicable')->default(false);
                $table->decimal('sme_preference_percentage', 5, 2)->nullable();
                
                $table->timestamps();
            });
        }

        // ========================================
        // المرحلة 4: معايير التقييم والتأهيل
        // ========================================
        
        // جدول معايير التقييم الفني
        if (!Schema::hasTable('tender_technical_criteria')) {
            Schema::create('tender_technical_criteria', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->constrained()->onDelete('cascade');
                
                // تصنيف المعيار
                $table->enum('category', [
                    'experience',           // الخبرة العامة
                    'similar_experience',   // الخبرة في مشاريع مماثلة
                    'personnel',            // الكوادر الفنية
                    'equipment',            // المعدات
                    'methodology',          // منهجية التنفيذ
                    'work_program',         // البرنامج الزمني
                    'quality_plan',         // خطة الجودة
                    'safety_plan',          // خطة السلامة
                    'financial_capability', // القدرة المالية
                    'other'
                ]);
                
                $table->string('criterion_ar', 500);
                $table->string('criterion_en', 500)->nullable();
                $table->text('description')->nullable();
                
                // الوزن والتقييم
                $table->decimal('weight', 5, 2)->default(0);
                $table->decimal('max_score', 5, 2)->default(100);
                $table->decimal('minimum_score', 5, 2)->nullable(); // الحد الأدنى للنجاح
                
                // تفاصيل التقييم
                $table->json('scoring_guide')->nullable(); // دليل التسجيل
                $table->json('required_evidence')->nullable(); // الإثباتات المطلوبة
                
                $table->boolean('is_pass_fail')->default(false); // هل هو معيار نجاح/رسوب
                $table->boolean('is_mandatory')->default(true);
                $table->integer('sort_order')->default(0);
                
                $table->timestamps();
            });
        }

        // جدول تقييم المناقصين فنياً
        if (!Schema::hasTable('tender_technical_evaluations')) {
            Schema::create('tender_technical_evaluations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->constrained()->onDelete('cascade');
                $table->foreignId('competitor_id')->constrained('tender_competitors')->onDelete('cascade');
                $table->foreignId('criterion_id')->constrained('tender_technical_criteria')->onDelete('cascade');
                
                $table->decimal('score', 5, 2)->nullable();
                $table->decimal('weighted_score', 5, 2)->nullable();
                $table->boolean('is_passed')->nullable();
                $table->text('justification')->nullable();
                
                $table->foreignId('evaluated_by')->nullable()->constrained('users');
                $table->timestamp('evaluated_at')->nullable();
                
                $table->timestamps();
                
                $table->unique(['tender_id', 'competitor_id', 'criterion_id'], 'tech_eval_unique');
            });
        }

        // ========================================
        // المرحلة 5: نماذج العرض
        // ========================================
        
        // جدول كتاب عرض المناقصة (Bid Letter)
        if (!Schema::hasTable('tender_bid_letters')) {
            Schema::create('tender_bid_letters', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->constrained()->onDelete('cascade');
                
                // بيانات كتاب العرض
                $table->date('letter_date');
                $table->string('letter_number', 50)->nullable();
                
                // المبلغ
                $table->decimal('total_amount', 18, 3);
                $table->text('amount_in_words_ar');
                $table->text('amount_in_words_en')->nullable();
                $table->foreignId('currency_id')->constrained('currencies');
                
                // الخصومات/الزيادات
                $table->boolean('has_discount')->default(false);
                $table->decimal('discount_percentage', 5, 2)->nullable();
                $table->decimal('discount_amount', 18, 3)->nullable();
                $table->text('discount_conditions')->nullable();
                
                $table->boolean('has_alternatives')->default(false);
                $table->text('alternatives_description')->nullable();
                
                // فترة السريان والتنفيذ
                $table->integer('validity_days');
                $table->integer('completion_period_days');
                $table->date('expected_start_date')->nullable();
                
                // التعهدات (حسب الوثيقة)
                $table->boolean('accepts_general_conditions')->default(true);
                $table->boolean('accepts_special_conditions')->default(true);
                $table->boolean('examined_documents')->default(true);
                $table->boolean('visited_site')->default(false);
                $table->boolean('no_conflict_of_interest')->default(true);
                $table->boolean('not_blacklisted')->default(true);
                
                // التوقيع
                $table->string('authorized_signatory', 255);
                $table->string('signatory_position', 100);
                $table->string('signature_path', 500)->nullable();
                $table->string('stamp_path', 500)->nullable();
                
                $table->timestamps();
            });
        }

        // جدول العرض الفني المنظم
        if (!Schema::hasTable('tender_technical_proposals')) {
            Schema::create('tender_technical_proposals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->constrained()->onDelete('cascade');
                
                // ملفات العرض الفني
                $table->string('company_profile_path', 500)->nullable();
                $table->string('organization_chart_path', 500)->nullable();
                $table->string('method_statement_path', 500)->nullable();
                $table->string('work_program_path', 500)->nullable();
                $table->string('quality_plan_path', 500)->nullable();
                $table->string('safety_plan_path', 500)->nullable();
                $table->string('environmental_plan_path', 500)->nullable();
                
                // القوائم المالية
                $table->string('financial_statements_path', 500)->nullable();
                $table->string('bank_reference_path', 500)->nullable();
                $table->decimal('average_annual_turnover', 18, 3)->nullable();
                $table->decimal('current_liquid_assets', 18, 3)->nullable();
                
                // الشهادات
                $table->string('classification_certificate_path', 500)->nullable();
                $table->string('classification_category', 50)->nullable();
                $table->date('classification_expiry')->nullable();
                $table->string('registration_certificate_path', 500)->nullable();
                $table->string('tax_clearance_path', 500)->nullable();
                $table->date('tax_clearance_date')->nullable();
                $table->string('social_security_clearance_path', 500)->nullable();
                $table->date('social_security_clearance_date')->nullable();
                
                // بيانات الإنجاز
                $table->integer('total_similar_projects')->default(0);
                $table->decimal('total_similar_value', 18, 3)->nullable();
                
                // الحالة
                $table->enum('status', ['draft', 'ready', 'submitted'])->default('draft');
                $table->decimal('completeness_percentage', 5, 2)->default(0);
                
                $table->timestamps();
            });
        }

        // جدول المشاريع المماثلة (Similar Projects Experience)
        if (!Schema::hasTable('tender_similar_projects')) {
            Schema::create('tender_similar_projects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('technical_proposal_id')->constrained('tender_technical_proposals')->onDelete('cascade');
                
                $table->string('project_name', 500);
                $table->string('client_name', 255);
                $table->string('client_contact', 255)->nullable();
                $table->string('client_phone', 50)->nullable();
                $table->string('client_email', 255)->nullable();
                
                $table->string('location', 255)->nullable();
                $table->decimal('contract_value', 18, 3);
                $table->foreignId('currency_id')->nullable()->constrained('currencies');
                
                $table->date('start_date');
                $table->date('completion_date')->nullable();
                $table->date('actual_completion_date')->nullable();
                
                $table->text('scope_of_work')->nullable();
                $table->string('consultant_name', 255)->nullable();
                
                // نسبة المشاركة (في حالة الائتلاف)
                $table->decimal('participation_percentage', 5, 2)->default(100);
                
                // الإثباتات
                $table->string('completion_certificate_path', 500)->nullable();
                $table->string('contract_copy_path', 500)->nullable();
                
                $table->text('notes')->nullable();
                $table->integer('sort_order')->default(0);
                
                $table->timestamps();
            });
        }

        // جدول الكوادر الفنية الرئيسية
        if (!Schema::hasTable('tender_key_personnel')) {
            Schema::create('tender_key_personnel', function (Blueprint $table) {
                $table->id();
                $table->foreignId('technical_proposal_id')->constrained('tender_technical_proposals')->onDelete('cascade');
                
                $table->string('position', 100); // المنصب
                $table->string('name', 255);
                $table->string('nationality', 100)->nullable();
                
                $table->string('qualification', 255)->nullable();
                $table->string('specialization', 255)->nullable();
                $table->integer('experience_years')->nullable();
                $table->integer('similar_experience_years')->nullable();
                
                // المستندات
                $table->string('cv_path', 500)->nullable();
                $table->string('certificate_path', 500)->nullable();
                $table->string('registration_path', 500)->nullable(); // تسجيل نقابة المهندسين
                
                $table->boolean('is_permanent_employee')->default(true);
                $table->string('employment_proof_path', 500)->nullable();
                
                $table->text('notes')->nullable();
                $table->integer('sort_order')->default(0);
                
                $table->timestamps();
            });
        }

        // جدول قائمة المعدات للعطاء
        if (!Schema::hasTable('tender_equipment_list')) {
            Schema::create('tender_equipment_list', function (Blueprint $table) {
                $table->id();
                $table->foreignId('technical_proposal_id')->constrained('tender_technical_proposals')->onDelete('cascade');
                
                $table->string('equipment_type', 255);
                $table->string('make_model', 255)->nullable();
                $table->integer('quantity')->default(1);
                $table->integer('year_of_manufacture')->nullable();
                $table->string('condition', 50)->nullable();
                $table->decimal('capacity', 10, 2)->nullable();
                $table->string('capacity_unit', 50)->nullable();
                
                $table->enum('ownership', ['owned', 'leased', 'to_purchase', 'to_lease']);
                $table->string('current_location', 255)->nullable();
                
                // الإثباتات
                $table->string('ownership_proof_path', 500)->nullable();
                $table->string('registration_path', 500)->nullable();
                
                $table->text('notes')->nullable();
                $table->integer('sort_order')->default(0);
                
                $table->timestamps();
            });
        }

        // جدول العرض المالي
        if (!Schema::hasTable('tender_financial_proposals')) {
            Schema::create('tender_financial_proposals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->constrained()->onDelete('cascade');
                
                // الملفات
                $table->string('priced_boq_path', 500)->nullable();
                $table->string('price_summary_path', 500)->nullable();
                $table->string('price_breakdown_path', 500)->nullable();
                
                // المبالغ
                $table->decimal('direct_cost', 18, 3)->default(0);
                $table->decimal('indirect_cost', 18, 3)->default(0);
                $table->decimal('contingency', 18, 3)->default(0);
                $table->decimal('profit', 18, 3)->default(0);
                $table->decimal('total_before_tax', 18, 3)->default(0);
                $table->decimal('tax_amount', 18, 3)->default(0);
                $table->decimal('grand_total', 18, 3)->default(0);
                
                $table->foreignId('currency_id')->constrained('currencies');
                
                // التوزيع حسب الحزم (إن وجدت)
                $table->json('package_prices')->nullable();
                
                // الحالة
                $table->enum('status', ['draft', 'ready', 'submitted'])->default('draft');
                
                $table->timestamps();
            });
        }

        // ========================================
        // المرحلة 6: تأمينات وكفالات العطاء
        // ========================================
        
        if (!Schema::hasTable('tender_bonds')) {
            Schema::create('tender_bonds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->constrained()->onDelete('cascade');
                
                // نوع الكفالة
                $table->enum('bond_type', [
                    'bid_security',          // تأمين دخول العطاء
                    'performance_bond',      // كفالة حسن التنفيذ
                    'advance_payment_bond',  // كفالة الدفعة المقدمة
                    'retention_bond',        // كفالة المحتجزات
                    'maintenance_bond'       // كفالة الصيانة
                ]);
                
                // بيانات الكفالة
                $table->string('bond_number', 100);
                $table->date('issue_date');
                $table->date('expiry_date');
                $table->decimal('amount', 18, 3);
                $table->foreignId('currency_id')->constrained('currencies');
                
                // الجهة المصدرة
                $table->enum('issuer_type', ['bank', 'insurance_company']);
                $table->string('issuer_name', 255);
                $table->string('issuer_branch', 255)->nullable();
                $table->string('issuer_address', 500)->nullable();
                $table->string('issuer_contact', 255)->nullable();
                
                // المستفيد
                $table->string('beneficiary_name', 255);
                $table->text('beneficiary_address')->nullable();
                
                // الملف
                $table->string('document_path', 500)->nullable();
                
                // الحالة
                $table->enum('status', [
                    'draft',      // مسودة
                    'requested',  // مطلوبة
                    'issued',     // صادرة
                    'submitted',  // مقدمة
                    'released',   // محررة
                    'extended',   // ممددة
                    'claimed',    // مطالب بها
                    'expired'     // منتهية
                ])->default('draft');
                
                // التمديدات
                $table->integer('extension_count')->default(0);
                $table->date('original_expiry_date')->nullable();
                
                // التكاليف
                $table->decimal('issuance_fee', 10, 2)->nullable();
                $table->decimal('commission_rate', 5, 4)->nullable();
                $table->decimal('total_cost', 10, 2)->nullable();
                
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // جدول تمديدات الكفالات
        if (!Schema::hasTable('tender_bond_extensions')) {
            Schema::create('tender_bond_extensions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bond_id')->constrained('tender_bonds')->onDelete('cascade');
                
                $table->integer('extension_number');
                $table->date('request_date');
                $table->date('previous_expiry_date');
                $table->date('new_expiry_date');
                $table->decimal('extension_fee', 10, 2)->nullable();
                
                $table->string('document_path', 500)->nullable();
                $table->text('reason')->nullable();
                
                $table->foreignId('requested_by')->nullable()->constrained('users');
                $table->timestamps();
            });
        }

        // ========================================
        // المرحلة 7: التقديم والتسليم
        // ========================================
        
        if (!Schema::hasTable('tender_submissions')) {
            Schema::create('tender_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->constrained()->onDelete('cascade');
                
                // بيانات التقديم
                $table->datetime('submission_datetime');
                $table->enum('submission_method', ['hand_delivery', 'registered_mail', 'electronic']);
                
                // تفاصيل التسليم اليدوي
                $table->string('delivered_by', 255)->nullable();
                $table->string('delivery_id_number', 50)->nullable();
                $table->string('receiver_name', 255)->nullable();
                $table->string('receipt_number', 100)->nullable();
                $table->string('receipt_path', 500)->nullable();
                
                // تفاصيل البريد المسجل
                $table->string('tracking_number', 100)->nullable();
                $table->string('courier_name', 100)->nullable();
                
                // تفاصيل التقديم الإلكتروني
                $table->string('submission_reference', 100)->nullable();
                $table->string('confirmation_number', 100)->nullable();
                
                // عدد النسخ
                $table->integer('original_copies')->default(1);
                $table->integer('additional_copies')->default(0);
                $table->boolean('technical_separate')->default(true); // المظروف الفني منفصل
                $table->boolean('financial_separate')->default(true); // المظروف المالي منفصل
                
                // قائمة المحتويات
                $table->json('envelope_contents')->nullable();
                
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->timestamps();
            });
        }

        // ========================================
        // المرحلة 8: الإيضاحات والتعديلات
        // ========================================
        
        // جدول الاستيضاحات
        if (!Schema::hasTable('tender_clarifications')) {
            Schema::create('tender_clarifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->constrained()->onDelete('cascade');
                
                // السؤال
                $table->string('question_number', 50);
                $table->date('question_date');
                $table->text('question_ar');
                $table->text('question_en')->nullable();
                $table->enum('question_source', ['our_company', 'other_bidder', 'procuring_entity']);
                
                // الجواب
                $table->text('answer_ar')->nullable();
                $table->text('answer_en')->nullable();
                $table->date('answer_date')->nullable();
                $table->string('answer_reference', 100)->nullable();
                $table->string('answer_document_path', 500)->nullable();
                
                // التأثير
                $table->boolean('affects_boq')->default(false);
                $table->boolean('affects_price')->default(false);
                $table->boolean('affects_schedule')->default(false);
                $table->text('impact_notes')->nullable();
                
                $table->enum('status', ['pending', 'answered', 'no_response'])->default('pending');
                $table->timestamps();
            });
        }

        // جدول ملحقات العطاء (Addenda)
        if (!Schema::hasTable('tender_addenda')) {
            Schema::create('tender_addenda', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->constrained()->onDelete('cascade');
                
                $table->string('addendum_number', 50);
                $table->date('issue_date');
                $table->string('title_ar', 500);
                $table->string('title_en', 500)->nullable();
                $table->text('description_ar');
                $table->text('description_en')->nullable();
                
                // التغييرات
                $table->boolean('modifies_boq')->default(false);
                $table->boolean('modifies_drawings')->default(false);
                $table->boolean('modifies_specifications')->default(false);
                $table->boolean('modifies_conditions')->default(false);
                $table->boolean('extends_deadline')->default(false);
                
                // تمديد الموعد
                $table->datetime('original_deadline')->nullable();
                $table->datetime('new_deadline')->nullable();
                
                $table->string('document_path', 500)->nullable();
                $table->json('affected_items')->nullable();
                
                $table->timestamps();
            });
        }

        // ========================================
        // المرحلة 9: فتح العروض والتقييم
        // ========================================
        
        // تحسين جدول نتائج فتح المظاريف
        if (Schema::hasTable('tender_opening_results')) {
            Schema::table('tender_opening_results', function (Blueprint $table) {
                if (!Schema::hasColumn('tender_opening_results', 'envelope_type')) {
                    $table->enum('envelope_type', ['technical', 'financial', 'combined'])->default('combined')->after('tender_id');
                }
                if (!Schema::hasColumn('tender_opening_results', 'opening_committee')) {
                    $table->json('opening_committee')->nullable(); // أعضاء لجنة الفتح
                }
                if (!Schema::hasColumn('tender_opening_results', 'bidder_representatives')) {
                    $table->json('bidder_representatives')->nullable(); // ممثلي المناقصين الحاضرين
                }
                if (!Schema::hasColumn('tender_opening_results', 'minutes_path')) {
                    $table->string('minutes_path', 500)->nullable(); // محضر الفتح
                }
                if (!Schema::hasColumn('tender_opening_results', 'is_responsive')) {
                    $table->boolean('is_responsive')->nullable(); // هل العرض مستجيب
                }
                if (!Schema::hasColumn('tender_opening_results', 'rejection_reason')) {
                    $table->text('rejection_reason')->nullable();
                }
            });
        }

        // جدول محاضر لجان التقييم
        if (!Schema::hasTable('tender_evaluation_committees')) {
            Schema::create('tender_evaluation_committees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->constrained()->onDelete('cascade');
                
                $table->enum('committee_type', ['opening', 'technical_evaluation', 'financial_evaluation', 'final']);
                $table->string('committee_name', 255);
                
                // أعضاء اللجنة
                $table->json('members'); // [{name, position, organization, role}]
                
                // الاجتماعات
                $table->date('meeting_date');
                $table->string('meeting_location', 255)->nullable();
                
                // النتائج والتوصيات
                $table->text('findings')->nullable();
                $table->text('recommendations')->nullable();
                
                // المحضر
                $table->string('minutes_path', 500)->nullable();
                
                $table->enum('status', ['scheduled', 'completed', 'adjourned'])->default('scheduled');
                $table->timestamps();
            });
        }

        // ========================================
        // المرحلة 10: قرار الإحالة
        // ========================================
        
        if (!Schema::hasTable('tender_award_decisions')) {
            Schema::create('tender_award_decisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->constrained()->onDelete('cascade');
                
                // القرار
                $table->enum('decision_type', [
                    'award_to_winner',      // إحالة للفائز
                    'award_to_second',      // إحالة للثاني
                    'cancel_tender',        // إلغاء المناقصة
                    'rebid',                // إعادة الطرح
                    'negotiate'             // التفاوض
                ]);
                
                $table->date('decision_date');
                $table->foreignId('winner_competitor_id')->nullable()->constrained('tender_competitors');
                $table->decimal('award_amount', 18, 3)->nullable();
                $table->foreignId('currency_id')->nullable()->constrained('currencies');
                
                // مبررات القرار
                $table->text('justification_ar');
                $table->text('justification_en')->nullable();
                
                // الإعلان المبدئي (فترة التوقف)
                $table->date('preliminary_announcement_date')->nullable();
                $table->integer('standstill_period_days')->default(7);
                $table->date('standstill_end_date')->nullable();
                
                // الاعتراضات
                $table->boolean('objections_received')->default(false);
                $table->json('objections_details')->nullable();
                
                // القرار النهائي
                $table->date('final_decision_date')->nullable();
                $table->text('final_decision_notes')->nullable();
                
                // خطاب الإحالة
                $table->string('award_letter_number', 100)->nullable();
                $table->date('award_letter_date')->nullable();
                $table->string('award_letter_path', 500)->nullable();
                
                // اللجنة
                $table->string('committee_name', 255)->nullable();
                $table->json('committee_members')->nullable();
                
                $table->enum('status', ['preliminary', 'final', 'cancelled'])->default('preliminary');
                $table->timestamps();
            });
        }

        // ========================================
        // تحسينات على جدول العطاءات الرئيسي
        // ========================================
        
        if (Schema::hasTable('tenders')) {
            Schema::table('tenders', function (Blueprint $table) {
                // إضافة حقول جديدة حسب الوثيقة
                if (!Schema::hasColumn('tenders', 'tender_scope')) {
                    $table->enum('tender_scope', ['local', 'international'])->default('local')->after('tender_method');
                }
                if (!Schema::hasColumn('tenders', 'contract_type')) {
                    $table->enum('contract_type', [
                        'measurement',  // قياس (re-measurement)
                        'lump_sum',     // مبلغ مقطوع
                        'unit_rate',    // أسعار وحدات
                        'cost_plus',    // تكلفة + نسبة
                        'turnkey'       // تسليم مفتاح
                    ])->default('measurement')->after('tender_scope');
                }
                if (!Schema::hasColumn('tenders', 'price_adjustment_applicable')) {
                    $table->boolean('price_adjustment_applicable')->default(false);
                }
                if (!Schema::hasColumn('tenders', 'price_adjustment_formula')) {
                    $table->text('price_adjustment_formula')->nullable();
                }
                if (!Schema::hasColumn('tenders', 'completion_period_days')) {
                    $table->integer('completion_period_days')->nullable();
                }
                if (!Schema::hasColumn('tenders', 'defects_liability_period_days')) {
                    $table->integer('defects_liability_period_days')->default(365);
                }
                if (!Schema::hasColumn('tenders', 'liquidated_damages_percentage')) {
                    $table->decimal('liquidated_damages_percentage', 5, 2)->default(0.1);
                }
                if (!Schema::hasColumn('tenders', 'max_liquidated_damages_percentage')) {
                    $table->decimal('max_liquidated_damages_percentage', 5, 2)->default(10);
                }
            });
        }

        // ========================================
        // جدول حالات العطاء (Tender Workflow States)
        // ========================================
        
        if (!Schema::hasTable('tender_workflow_logs')) {
            Schema::create('tender_workflow_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tender_id')->constrained()->onDelete('cascade');
                
                $table->string('from_status', 50)->nullable();
                $table->string('to_status', 50);
                $table->datetime('transition_at');
                
                $table->text('notes')->nullable();
                $table->foreignId('performed_by')->nullable()->constrained('users');
                
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // حذف الجداول بالترتيب العكسي
        Schema::dropIfExists('tender_workflow_logs');
        Schema::dropIfExists('tender_award_decisions');
        Schema::dropIfExists('tender_evaluation_committees');
        Schema::dropIfExists('tender_addenda');
        Schema::dropIfExists('tender_clarifications');
        Schema::dropIfExists('tender_submissions');
        Schema::dropIfExists('tender_bond_extensions');
        Schema::dropIfExists('tender_bonds');
        Schema::dropIfExists('tender_financial_proposals');
        Schema::dropIfExists('tender_equipment_list');
        Schema::dropIfExists('tender_key_personnel');
        Schema::dropIfExists('tender_similar_projects');
        Schema::dropIfExists('tender_technical_proposals');
        Schema::dropIfExists('tender_bid_letters');
        Schema::dropIfExists('tender_technical_evaluations');
        Schema::dropIfExists('tender_technical_criteria');
        Schema::dropIfExists('tender_bid_data_sheets');
        Schema::dropIfExists('tender_bidder_eligibility');
        Schema::dropIfExists('tender_eligibility_requirements');
        Schema::dropIfExists('tender_announcements');
        
        // إزالة الأعمدة المضافة
        if (Schema::hasTable('tenders')) {
            Schema::table('tenders', function (Blueprint $table) {
                $columns = [
                    'tender_scope', 'contract_type', 'price_adjustment_applicable',
                    'price_adjustment_formula', 'completion_period_days',
                    'defects_liability_period_days', 'liquidated_damages_percentage',
                    'max_liquidated_damages_percentage'
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('tenders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
        
        if (Schema::hasTable('tender_opening_results')) {
            Schema::table('tender_opening_results', function (Blueprint $table) {
                $columns = [
                    'envelope_type', 'opening_committee', 'bidder_representatives',
                    'minutes_path', 'is_responsive', 'rejection_reason'
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('tender_opening_results', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
