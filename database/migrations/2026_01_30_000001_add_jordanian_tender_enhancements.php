<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إضافة تحسينات العطاءات حسب الوثائق القياسية الأردنية
 * - الأفضليات السعرية
 * - إجراءات الاعتراض
 * - الائتلافات
 * - الإقرارات
 */
return new class extends Migration
{
    public function up(): void
    {
        // ==========================================
        // 1. إضافة حقول جديدة لجدول العطاءات
        // ==========================================
        Schema::table('tenders', function (Blueprint $table) {
            // === معلومات التصنيف الموسعة ===
            $table->string('classification_field', 100)->nullable()->after('required_classification')->comment('مجال التصنيف');
            $table->string('classification_specialty', 100)->nullable()->after('classification_field')->comment('اختصاص التصنيف');
            $table->string('classification_category', 50)->nullable()->after('classification_specialty')->comment('فئة التصنيف');
            
            // === إجراءات الإحالة والاعتراض ===
            $table->date('preliminary_award_date')->nullable()->comment('تاريخ الإحالة المبدئية');
            $table->date('objection_deadline')->nullable()->comment('آخر موعد للاعتراض');
            $table->integer('objection_period_days')->default(5)->comment('مدة فترة الاعتراض (5-7 أيام)');
            $table->date('final_award_date')->nullable()->comment('تاريخ الإحالة النهائية');
            $table->boolean('is_objection_period_active')->default(false)->comment('فترة الاعتراض نشطة');
            
            // === الرسوم المقررة ===
            $table->decimal('prescribed_fees', 18, 3)->nullable()->comment('الرسوم المقررة');
            $table->decimal('prescribed_fees_percentage', 5, 2)->nullable()->comment('نسبة الرسوم المقررة');
            $table->boolean('prescribed_fees_paid')->default(false)->comment('تم دفع الرسوم');
            $table->date('prescribed_fees_paid_date')->nullable()->comment('تاريخ دفع الرسوم');
            
            // === الأفضليات السعرية ===
            $table->boolean('sme_preference_applies')->default(true)->comment('تطبق أفضلية المنشآت الصغيرة والمتوسطة');
            $table->boolean('women_youth_preference_applies')->default(true)->comment('تطبق أفضلية المرأة والشباب');
            $table->boolean('disability_preference_applies')->default(true)->comment('تطبق أفضلية ذوي الإعاقة');
            
            // === المقاولون الفرعيون ===
            $table->decimal('max_subcontract_percentage', 5, 2)->default(33)->comment('الحد الأقصى لنسبة المقاولة الفرعية');
            $table->decimal('local_subcontract_percentage', 5, 2)->default(10)->comment('نسبة المقاولين من المحافظة');
            $table->boolean('has_named_subcontractors')->default(false)->comment('يوجد مقاولون فرعيون مسمون');
            
            // === اجتماع ما قبل المناقصة ===
            $table->boolean('pre_bid_meeting_required')->default(false)->comment('اجتماع ما قبل المناقصة مطلوب');
            $table->datetime('pre_bid_meeting_date')->nullable()->comment('موعد اجتماع ما قبل المناقصة');
            $table->string('pre_bid_meeting_location', 500)->nullable()->comment('مكان الاجتماع');
            $table->text('pre_bid_meeting_minutes')->nullable()->comment('محضر الاجتماع');
            
            // === التقديم الإلكتروني ===
            $table->boolean('electronic_submission_allowed')->default(false)->comment('يسمح بالتقديم الإلكتروني');
            $table->string('electronic_submission_url', 500)->nullable()->comment('رابط التقديم الإلكتروني');
            
            // === كتاب القبول ===
            $table->date('acceptance_letter_date')->nullable()->comment('تاريخ كتاب القبول');
            $table->string('acceptance_letter_number', 100)->nullable()->comment('رقم كتاب القبول');
            $table->text('acceptance_letter_notes')->nullable()->comment('ملاحظات كتاب القبول');
            
            // === مدة توقيع العقد ===
            $table->integer('contract_signing_period_days')->default(14)->comment('مدة توقيع العقد (أيام)');
            $table->date('contract_signing_deadline')->nullable()->comment('آخر موعد لتوقيع العقد');
        });

        // ==========================================
        // 2. جدول الائتلافات
        // ==========================================
        Schema::create('tender_consortiums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->string('consortium_name', 255)->comment('اسم الائتلاف');
            $table->foreignId('lead_company_id')->nullable()->constrained('companies')->nullOnDelete()->comment('الشركة الرئيسية');
            $table->string('lead_company_name', 255)->nullable()->comment('اسم الشركة الرئيسية (يدوي)');
            $table->enum('agreement_type', ['full_agreement', 'letter_of_intent'])->default('letter_of_intent')->comment('نوع الاتفاقية');
            $table->date('agreement_date')->nullable()->comment('تاريخ الاتفاقية');
            $table->string('agreement_file', 500)->nullable()->comment('ملف الاتفاقية');
            $table->boolean('is_certified')->default(false)->comment('مصدقة أصولياً');
            $table->date('certification_date')->nullable()->comment('تاريخ التصديق');
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ==========================================
        // 3. جدول أعضاء الائتلاف
        // ==========================================
        Schema::create('tender_consortium_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consortium_id')->constrained('tender_consortiums')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('company_name', 255)->comment('اسم الشركة');
            $table->string('classification', 100)->nullable()->comment('التصنيف');
            $table->decimal('share_percentage', 5, 2)->nullable()->comment('نسبة المشاركة');
            $table->text('scope_of_work')->nullable()->comment('نطاق العمل');
            $table->boolean('is_lead')->default(false)->comment('رئيس الائتلاف');
            $table->string('authorized_signatory', 255)->nullable()->comment('المفوض بالتوقيع');
            $table->string('signatory_title', 100)->nullable()->comment('صفة المفوض');
            $table->string('contact_person', 255)->nullable()->comment('جهة الاتصال');
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ==========================================
        // 4. جدول الإقرارات
        // ==========================================
        Schema::create('tender_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bidder_id')->nullable()->constrained('companies')->nullOnDelete()->comment('مقدم الإقرار');
            $table->string('bidder_name', 255)->nullable()->comment('اسم مقدم الإقرار');
            $table->enum('declaration_type', [
                'esmp_commitment',           // التزام بخطة الإدارة البيئية والاجتماعية
                'code_of_conduct',           // مدونة قواعد السلوك
                'other_payments',            // إقرار الدفعات الأخرى
                'prohibited_payments',       // إقرار الدفعات الممنوعة
                'no_conflict_of_interest',   // عدم تضارب المصالح
                'anti_corruption',           // مكافحة الفساد
                'eligibility',               // الأهلية
                'validity_acceptance',       // قبول فترة الصلاحية
            ])->comment('نوع الإقرار');
            $table->string('declaration_title', 255)->comment('عنوان الإقرار');
            $table->text('declaration_text')->nullable()->comment('نص الإقرار');
            $table->boolean('is_signed')->default(false)->comment('تم التوقيع');
            $table->string('signatory_name', 255)->nullable()->comment('اسم الموقع');
            $table->string('signatory_title', 100)->nullable()->comment('صفة الموقع');
            $table->date('signature_date')->nullable()->comment('تاريخ التوقيع');
            $table->string('signature_file', 500)->nullable()->comment('ملف التوقيع');
            $table->boolean('is_required')->default(true)->comment('إقرار إلزامي');
            $table->enum('status', ['pending', 'submitted', 'verified', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ==========================================
        // 5. جدول الاعتراضات
        // ==========================================
        Schema::create('tender_objections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->foreignId('objector_id')->nullable()->constrained('companies')->nullOnDelete()->comment('المعترض');
            $table->string('objector_name', 255)->comment('اسم المعترض');
            $table->string('objector_contact', 255)->nullable()->comment('جهة اتصال المعترض');
            $table->string('objector_phone', 50)->nullable();
            $table->string('objector_email', 255)->nullable();
            $table->enum('objection_type', [
                'preliminary_award',     // اعتراض على الإحالة المبدئية
                'documents',             // اعتراض على الوثائق
                'evaluation',            // اعتراض على التقييم
                'qualification',         // اعتراض على التأهيل
                'procedure',             // اعتراض على الإجراءات
                'other',                 // أخرى
            ])->comment('نوع الاعتراض');
            $table->string('objection_number', 100)->nullable()->comment('رقم الاعتراض');
            $table->date('objection_date')->comment('تاريخ تقديم الاعتراض');
            $table->text('objection_subject')->comment('موضوع الاعتراض');
            $table->text('objection_details')->comment('تفاصيل الاعتراض');
            $table->text('legal_basis')->nullable()->comment('السند القانوني');
            $table->text('requested_action')->nullable()->comment('الإجراء المطلوب');
            $table->string('attachments', 1000)->nullable()->comment('المرفقات');
            $table->enum('status', [
                'submitted',             // مقدم
                'under_review',          // قيد الدراسة
                'accepted',              // مقبول
                'partially_accepted',    // مقبول جزئياً
                'rejected',              // مرفوض
                'escalated',             // مصعّد للجنة الشكاوى
            ])->default('submitted');
            $table->text('committee_decision')->nullable()->comment('قرار اللجنة');
            $table->date('decision_date')->nullable()->comment('تاريخ القرار');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_justification')->nullable()->comment('مبررات القرار');
            $table->boolean('is_escalated_to_complaints')->default(false)->comment('مصعّد للجنة مراجعة الشكاوى');
            $table->date('escalation_date')->nullable();
            $table->decimal('complaint_fee', 10, 2)->nullable()->default(500)->comment('رسم الشكوى');
            $table->boolean('complaint_fee_paid')->default(false);
            $table->text('complaints_committee_decision')->nullable()->comment('قرار لجنة مراجعة الشكاوى');
            $table->date('complaints_decision_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['tender_id', 'status']);
        });

        // ==========================================
        // 6. جدول الأفضليات السعرية للعطاءات
        // ==========================================
        Schema::create('tender_price_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bidder_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('bidder_name', 255)->comment('اسم المناقص');
            $table->enum('preference_type', [
                'sme',                   // المنشآت الصغيرة والمتوسطة
                'women_ownership',       // ملكية المرأة >51%
                'youth_ownership',       // ملكية الشباب >51%
                'women_management',      // إدارة المرأة
                'youth_management',      // إدارة الشباب
                'disability',            // ذوي الإعاقة >51%
            ])->comment('نوع الأفضلية');
            $table->decimal('preference_percentage', 5, 2)->comment('نسبة الأفضلية');
            $table->decimal('original_price', 18, 3)->comment('السعر الأصلي');
            $table->decimal('adjusted_price', 18, 3)->comment('السعر المعدل للتقييم');
            $table->decimal('discount_amount', 18, 3)->comment('قيمة الخصم');
            $table->text('eligibility_proof')->nullable()->comment('إثبات الأهلية');
            $table->string('eligibility_documents', 1000)->nullable()->comment('وثائق الأهلية');
            $table->boolean('is_verified')->default(false)->comment('تم التحقق');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('verified_at')->nullable();
            $table->boolean('is_applied')->default(false)->comment('تم التطبيق');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['tender_id', 'bidder_id', 'preference_type'], 'tender_bidder_preference_unique');
        });

        // ==========================================
        // 7. جدول المقاولين الفرعيين المقترحين
        // ==========================================
        Schema::create('tender_proposed_subcontractors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bidder_id')->nullable()->constrained('companies')->nullOnDelete()->comment('المناقص');
            $table->string('bidder_name', 255)->nullable();
            $table->foreignId('subcontractor_id')->nullable()->constrained('suppliers')->nullOnDelete()->comment('المقاول الفرعي');
            $table->string('subcontractor_name', 255)->comment('اسم المقاول الفرعي');
            $table->string('subcontractor_classification', 100)->nullable()->comment('تصنيف المقاول الفرعي');
            $table->text('work_scope')->comment('نطاق العمل');
            $table->decimal('work_percentage', 5, 2)->comment('نسبة العمل من العقد');
            $table->decimal('work_value', 18, 3)->nullable()->comment('قيمة العمل');
            $table->boolean('is_from_governorate')->default(false)->comment('من أبناء المحافظة');
            $table->string('governorate', 100)->nullable()->comment('المحافظة');
            $table->boolean('is_specialized')->default(false)->comment('مقاول متخصص');
            $table->string('specialization', 255)->nullable()->comment('التخصص');
            $table->boolean('is_approved')->nullable()->comment('موافقة الجهة');
            $table->text('approval_notes')->nullable();
            $table->text('qualifications')->nullable()->comment('المؤهلات');
            $table->text('experience')->nullable()->comment('الخبرات');
            $table->timestamps();
        });

        // ==========================================
        // 8. جدول تصحيحات الأخطاء الحسابية
        // ==========================================
        Schema::create('tender_arithmetic_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bidder_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('bidder_name', 255);
            $table->enum('correction_type', [
                'unit_price_vs_total',       // تعارض سعر الوحدة والإجمالي
                'subtotal_addition',         // خطأ في جمع المجاميع الفرعية
                'words_vs_numbers',          // تعارض الكلمات والأرقام
                'missing_unit_price',        // سعر وحدة مفقود
                'discount_calculation',      // حساب الخصم
                'unpriced_item',             // بند غير مسعر
                'unclear_price',             // سعر غير واضح
                'front_loading',             // أسعار مرتفعة في البداية
                'abnormally_low',            // سعر منخفض بشكل غير طبيعي
                'other',                     // أخرى
            ])->comment('نوع التصحيح');
            $table->string('item_number', 50)->nullable()->comment('رقم البند');
            $table->text('item_description')->nullable()->comment('وصف البند');
            $table->decimal('original_value', 18, 3)->comment('القيمة الأصلية');
            $table->decimal('corrected_value', 18, 3)->comment('القيمة المصححة');
            $table->decimal('difference', 18, 3)->comment('الفرق');
            $table->text('correction_basis')->nullable()->comment('أساس التصحيح');
            $table->text('correction_rule')->nullable()->comment('القاعدة المطبقة');
            $table->boolean('bidder_accepted')->nullable()->comment('موافقة المناقص');
            $table->date('notification_date')->nullable()->comment('تاريخ الإبلاغ');
            $table->date('response_date')->nullable()->comment('تاريخ الرد');
            $table->boolean('bid_rejected_for_refusal')->default(false)->comment('رفض العرض لعدم القبول');
            $table->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_arithmetic_corrections');
        Schema::dropIfExists('tender_proposed_subcontractors');
        Schema::dropIfExists('tender_price_preferences');
        Schema::dropIfExists('tender_objections');
        Schema::dropIfExists('tender_declarations');
        Schema::dropIfExists('tender_consortium_members');
        Schema::dropIfExists('tender_consortiums');
        
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropColumn([
                'classification_field',
                'classification_specialty',
                'classification_category',
                'preliminary_award_date',
                'objection_deadline',
                'objection_period_days',
                'final_award_date',
                'is_objection_period_active',
                'prescribed_fees',
                'prescribed_fees_percentage',
                'prescribed_fees_paid',
                'prescribed_fees_paid_date',
                'sme_preference_applies',
                'women_youth_preference_applies',
                'disability_preference_applies',
                'max_subcontract_percentage',
                'local_subcontract_percentage',
                'has_named_subcontractors',
                'pre_bid_meeting_required',
                'pre_bid_meeting_date',
                'pre_bid_meeting_location',
                'pre_bid_meeting_minutes',
                'electronic_submission_allowed',
                'electronic_submission_url',
                'acceptance_letter_date',
                'acceptance_letter_number',
                'acceptance_letter_notes',
                'contract_signing_period_days',
                'contract_signing_deadline',
            ]);
        });
    }
};
