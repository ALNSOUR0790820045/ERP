<?php

namespace App\Models;

use App\Enums\BondType;
use App\Enums\OwnerType;
use App\Enums\SubmissionMethod;
use App\Enums\TenderMethod;
use App\Enums\TenderResult;
use App\Enums\TenderStatus;
use App\Enums\TenderType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tender extends Model
{
    use SoftDeletes;

    /**
     * القيم الافتراضية للحقول
     */
    protected $attributes = [
        'tender_scope' => 'local',
        'status' => 'new',
        'result' => 'pending',
        'decision' => 'pending',
        'is_direct_sale' => false,
        'is_english_tender' => false,
        'is_package_tender' => false,
        'pre_bid_meeting_required' => false,
        'allows_price_preferences' => false,
        'allows_subcontracting' => true,
        'allows_consortium' => true,
        'allow_arithmetic_corrections' => true,
        'words_over_numbers_precedence' => true,
    ];

    protected $fillable = [
        // التعريف
        'tender_number',
        'reference_number',
        'name_ar',
        'name_en',
        'description',
        
        // فرصة البيع المباشر
        'is_direct_sale',
        'customer_id',
        
        // التصنيف
        'tender_type',
        'tender_method',
        'project_type_id',
        'specialization_id',
        
        // ==========================================
        // الحقول الجديدة - حسب وثائق العطاءات الأردنية المعيارية
        // ==========================================
        
        // التصنيف والتخصص المطلوب
        'classification_field',
        'classification_specialty',
        'classification_category',
        'classification_scope',
        'classification_field_id',
        'classification_specialty_id',
        'classification_category_id',
        
        // عنوان الجهة المشترية
        'buyer_country',
        'buyer_city',
        'buyer_area',
        'buyer_street',
        'buyer_building',
        'buyer_po_box',
        
        // الجهة المستفيدة
        'beneficiary_id',
        
        // عنوان تقديم العروض
        'submission_country',
        'submission_city',
        'submission_area',
        'submission_street',
        'submission_building',
        'submission_notes',
        
        // رابط خرائط جوجل
        'google_maps_link',
        
        // هل المناقصة بالإنجليزية
        'is_english_tender',
        
        // فترة الاعتراض
        'objection_period_days',
        'objection_period_start',
        'objection_period_end',
        'objection_fee',
        
        // اجتماع ما قبل تقديم العطاءات
        'pre_bid_meeting_required',
        'pre_bid_meeting_date',
        'pre_bid_meeting_location',
        'pre_bid_meeting_minutes',
        
        // الأفضليات السعرية
        'allows_price_preferences',
        'sme_preference_percentage',
        'local_products_preference',
        
        // المقاولين الفرعيين
        'allows_subcontracting',
        'max_subcontracting_percentage',
        'local_subcontractor_percentage',
        
        // الائتلافات
        'allows_consortium',
        'max_consortium_members',
        
        // متطلبات إضافية
        'esmp_required',
        'code_of_conduct_required',
        'anti_corruption_declaration_required',
        'conflict_of_interest_declaration_required',
        
        // معايير الفحص الجوهري
        'technical_pass_score',
        'financial_weight',
        'technical_weight',
        
        // تصحيحات حسابية
        'allow_arithmetic_corrections',
        'words_over_numbers_precedence',
        
        // العملة وبيانات إضافية
        'currency_for_bid_evaluation',
        'exchange_rate_date',
        'incoterms',
        
        // ==========================================
        // الحقول الأصلية
        // ==========================================
        
        // الجهة المالكة
        'owner_type',
        'owner_id',
        'owner_name',
        'owner_contact_person',
        'owner_phone',
        'owner_email',
        'owner_address',
        
        // الاستشاري
        'consultant_id',
        'consultant_name',
        
        // الموقع
        'country',
        'city',
        'site_address',
        'latitude',
        'longitude',
        
        // التواريخ
        'publication_date',
        'documents_sale_start',
        'documents_sale_end',
        'site_visit_date',
        'questions_deadline',
        'submission_deadline',
        'opening_date',
        'validity_period',
        'expected_award_date',
        
        // القيم
        'estimated_value',
        'currency_id',
        'documents_price',
        
        // شراء العطاء
        'documents_purchased',
        'purchase_date',
        'purchase_receipt_number',
        'site_visit_mandatory',
        'site_visit_attended',
        'site_visit_notes',
        
        // الكفالات
        'bid_bond_type',
        'bid_bond_percentage',
        'bid_bond_amount',
        'performance_bond_percentage',
        'advance_payment_percentage',
        'retention_percentage',
        
        // المتطلبات
        'required_classification',
        'minimum_experience_years',
        'minimum_similar_projects',
        'minimum_project_value',
        'financial_requirements',
        'technical_requirements',
        'other_requirements',
        
        // التسعير
        'total_direct_cost',
        'total_overhead',
        'total_cost',
        'markup_percentage',
        'markup_amount',
        'submitted_price',
        
        // الحالة
        'status',
        'decision',
        'decision_date',
        'decision_by',
        'decision_notes',
        
        // التقديم
        'submission_date',
        'submission_method',
        'submitted_by',
        'receipt_number',
        
        // النتيجة
        'result',
        'award_date',
        'winner_name',
        'winning_price',
        'our_rank',
        'loss_reason',
        'lessons_learned',
        
        // العقد
        'contract_id',
        
        // التدقيق
        'created_by',
        'updated_by',
        
        // ==========================================
        // الحقول الجديدة المضافة
        // ==========================================
        
        // تجزئة المناقصة (الحزم)
        'is_package_tender',
        'package_count',
        'award_basis',
        
        // الموقع الإلكتروني للجهة المشترية
        'owner_website',
        
        // مصدر التمويل
        'funding_source',
        'funder_name',
        
        // الاستيضاحات
        'clarification_address',
        
        // التقديم الإلكتروني
        'electronic_submission',
        'submission_district',
        'submission_box_number',
        
        // تأمين الدخول
        'bid_bond_calculation',
        'bid_bond_validity_days',
        
        // ملاحظات إضافية
        'additional_notes',
        
        // نطاق المناقصة
        'tender_scope',
        
        // المستندات المطلوبة (JSON)
        'required_documents',
        
        // الحي
        'project_district',
    ];

    protected $casts = [
        'tender_type' => TenderType::class,
        'tender_method' => TenderMethod::class,
        'owner_type' => OwnerType::class,
        'bid_bond_type' => BondType::class,
        'submission_method' => SubmissionMethod::class,
        'status' => TenderStatus::class,
        'result' => TenderResult::class,
        
        // فرصة البيع المباشر
        'is_direct_sale' => 'boolean',
        'is_english_tender' => 'boolean',
        
        // ==========================================
        // الحقول الجديدة - حسب وثائق العطاءات الأردنية المعيارية
        // ==========================================
        
        // فترة الاعتراض
        'objection_period_start' => 'date',
        'objection_period_end' => 'date',
        'objection_fee' => 'decimal:2',
        'objection_period_days' => 'integer',
        
        // اجتماع ما قبل تقديم العطاءات
        'pre_bid_meeting_required' => 'boolean',
        'pre_bid_meeting_date' => 'datetime',
        
        // الأفضليات السعرية
        'allows_price_preferences' => 'boolean',
        'sme_preference_percentage' => 'decimal:2',
        'local_products_preference' => 'boolean',
        
        // المقاولين الفرعيين
        'allows_subcontracting' => 'boolean',
        'max_subcontracting_percentage' => 'decimal:2',
        'local_subcontractor_percentage' => 'decimal:2',
        
        // الائتلافات
        'allows_consortium' => 'boolean',
        'max_consortium_members' => 'integer',
        
        // المتطلبات
        'esmp_required' => 'boolean',
        'code_of_conduct_required' => 'boolean',
        'anti_corruption_declaration_required' => 'boolean',
        'conflict_of_interest_declaration_required' => 'boolean',
        
        // معايير التقييم
        'technical_pass_score' => 'decimal:2',
        'financial_weight' => 'decimal:2',
        'technical_weight' => 'decimal:2',
        
        // التصحيحات الحسابية
        'allow_arithmetic_corrections' => 'boolean',
        'words_over_numbers_precedence' => 'boolean',
        
        // العملة
        'exchange_rate_date' => 'date',
        
        // ==========================================
        // الحقول الأصلية
        // ==========================================
        
        'publication_date' => 'date',
        'documents_sale_start' => 'date',
        'documents_sale_end' => 'date',
        'site_visit_date' => 'datetime',
        'questions_deadline' => 'datetime',
        'submission_deadline' => 'datetime',
        'opening_date' => 'datetime',
        'decision_date' => 'date',
        'submission_date' => 'datetime',
        'award_date' => 'date',
        'expected_award_date' => 'date',
        
        // شراء العطاء
        'documents_purchased' => 'boolean',
        'purchase_date' => 'date',
        'site_visit_mandatory' => 'boolean',
        'site_visit_attended' => 'boolean',
        
        'estimated_value' => 'decimal:3',
        'documents_price' => 'decimal:2',
        'bid_bond_percentage' => 'decimal:2',
        'bid_bond_amount' => 'decimal:3',
        'performance_bond_percentage' => 'decimal:2',
        'advance_payment_percentage' => 'decimal:2',
        'retention_percentage' => 'decimal:2',
        'minimum_project_value' => 'decimal:3',
        'total_direct_cost' => 'decimal:3',
        'total_overhead' => 'decimal:3',
        'total_cost' => 'decimal:3',
        'markup_percentage' => 'decimal:2',
        'markup_amount' => 'decimal:3',
        'submitted_price' => 'decimal:3',
        'winning_price' => 'decimal:3',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        
        // ==========================================
        // الحقول الجديدة المضافة
        // ==========================================
        
        // تجزئة المناقصة (الحزم)
        'is_package_tender' => 'boolean',
        'package_count' => 'integer',
        
        // تأمين الدخول
        'bid_bond_validity_days' => 'integer',
        
        // المستندات المطلوبة (JSON)
        'required_documents' => 'array',
    ];

    // العلاقات
    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class);
    }

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
    
    // العميل - للبيع المباشر
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    
    // الجهة المستفيدة
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'beneficiary_id');
    }
    
    // مجال التصنيف
    public function classificationField(): BelongsTo
    {
        return $this->belongsTo(ClassificationField::class);
    }
    
    // اختصاص التصنيف
    public function classificationSpecialty(): BelongsTo
    {
        return $this->belongsTo(ClassificationSpecialty::class);
    }
    
    // فئة التصنيف
    public function classificationCategory(): BelongsTo
    {
        return $this->belongsTo(ClassificationCategory::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function decisionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_by');
    }

    // === علاقات مراحل العطاء ===
    
    // المرحلة 1: الرصد
    public function discoveries(): HasMany
    {
        return $this->hasMany(\App\Models\Tenders\TenderDiscovery::class);
    }
    
    // المرحلة 2: الدراسة
    public function siteVisits(): HasMany
    {
        return $this->hasMany(\App\Models\Tenders\TenderSiteVisit::class);
    }
    
    public function evaluations(): HasMany
    {
        return $this->hasMany(\App\Models\Tenders\TenderEvaluation::class);
    }
    
    public function purchaseApprovals(): HasMany
    {
        return $this->hasMany(\App\Models\Tenders\TenderPurchaseApproval::class);
    }
    
    // المرحلة 3: إعداد العرض
    public function proposalClosure(): HasOne
    {
        return $this->hasOne(\App\Models\Tenders\TenderProposalClosure::class);
    }
    
    // المرحلة 5-6: الترسية
    public function awardTracking(): HasOne
    {
        return $this->hasOne(\App\Models\Tenders\TenderAwardTracking::class);
    }
    
    public function projectConversion(): HasOne
    {
        return $this->hasOne(\App\Models\Tenders\TenderToProjectConversion::class);
    }
    
    public function stageLogs(): HasMany
    {
        return $this->hasMany(\App\Models\Tenders\TenderStageLog::class);
    }
    
    public function alerts(): HasMany
    {
        return $this->hasMany(\App\Models\Tenders\TenderAlert::class);
    }
    
    public function bondRenewals(): HasMany
    {
        return $this->hasMany(\App\Models\Tenders\TenderBondRenewal::class);
    }
    
    public function bondWithdrawals(): HasMany
    {
        return $this->hasMany(\App\Models\Tenders\TenderBondWithdrawal::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TenderDocument::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(TenderPurchase::class);
    }

    public function boqItems(): HasMany
    {
        return $this->hasMany(BoqItem::class);
    }

    public function overheads(): HasMany
    {
        return $this->hasMany(TenderOverhead::class);
    }

    public function openingResults(): HasMany
    {
        return $this->hasMany(TenderOpeningResult::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TenderActivity::class)->latest();
    }

    public function decision(): HasOne
    {
        return $this->hasOne(TenderDecision::class);
    }

    // العلاقات الجديدة حسب وثيقة العطاءات الأردنية
    public function announcements(): HasMany
    {
        return $this->hasMany(TenderAnnouncement::class);
    }

    public function bidDataSheet(): HasOne
    {
        return $this->hasOne(TenderBidDataSheet::class);
    }

    public function eligibilityRequirements(): HasMany
    {
        return $this->hasMany(TenderEligibilityRequirement::class);
    }

    public function technicalCriteria(): HasMany
    {
        return $this->hasMany(TenderTechnicalCriterion::class);
    }

    public function bidLetter(): HasOne
    {
        return $this->hasOne(TenderBidLetter::class);
    }

    public function technicalProposal(): HasOne
    {
        return $this->hasOne(TenderTechnicalProposal::class);
    }

    public function financialProposal(): HasOne
    {
        return $this->hasOne(TenderFinancialProposal::class);
    }

    public function bonds(): HasMany
    {
        return $this->hasMany(TenderBond::class);
    }

    public function submission(): HasOne
    {
        return $this->hasOne(TenderSubmission::class);
    }

    public function clarifications(): HasMany
    {
        return $this->hasMany(TenderClarification::class);
    }

    public function addenda(): HasMany
    {
        return $this->hasMany(TenderAddendum::class);
    }

    public function evaluationCommittees(): HasMany
    {
        return $this->hasMany(TenderEvaluationCommittee::class);
    }

    public function awardDecision(): HasOne
    {
        return $this->hasOne(TenderAwardDecision::class);
    }

    public function workflowLogs(): HasMany
    {
        return $this->hasMany(TenderWorkflowLog::class);
    }

    public function swotAnalyses(): HasMany
    {
        return $this->hasMany(TenderSwotAnalysis::class);
    }

    public function risks(): HasMany
    {
        return $this->hasMany(TenderRisk::class);
    }

    public function decisionCriteria(): HasMany
    {
        return $this->hasMany(TenderDecisionCriterion::class);
    }

    public function competitors(): HasMany
    {
        return $this->hasMany(TenderCompetitor::class);
    }

    // ==========================================
    // العلاقات الإضافية - حسب وثائق العطاءات الأردنية المعيارية
    // ==========================================

    /**
     * الائتلافات
     */
    public function consortiums(): HasMany
    {
        return $this->hasMany(\App\Models\Tenders\TenderConsortium::class);
    }

    /**
     * الإقرارات والتعهدات
     */
    public function declarations(): HasMany
    {
        return $this->hasMany(\App\Models\Tenders\TenderDeclaration::class);
    }

    /**
     * الاعتراضات
     */
    public function objections(): HasMany
    {
        return $this->hasMany(\App\Models\Tenders\TenderObjection::class);
    }

    /**
     * الأفضليات السعرية
     */
    public function pricePreferences(): HasMany
    {
        return $this->hasMany(\App\Models\Tenders\TenderPricePreference::class);
    }

    /**
     * المقاولين الفرعيين المقترحين
     */
    public function proposedSubcontractors(): HasMany
    {
        return $this->hasMany(\App\Models\Tenders\TenderProposedSubcontractor::class);
    }

    /**
     * تصحيحات الأخطاء الحسابية
     */
    public function arithmeticCorrections(): HasMany
    {
        return $this->hasMany(\App\Models\Tenders\TenderArithmeticCorrection::class);
    }

    // Accessors
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'en' && $this->name_en 
            ? $this->name_en 
            : $this->name_ar;
    }

    public function getDaysUntilSubmissionAttribute(): ?int
    {
        if (!$this->submission_deadline) {
            return null;
        }
        return now()->diffInDays($this->submission_deadline, false);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->days_until_submission !== null && $this->days_until_submission < 0;
    }

    public function getIsUrgentAttribute(): bool
    {
        return $this->days_until_submission !== null 
            && $this->days_until_submission >= 0 
            && $this->days_until_submission <= 7;
    }

    public function getPriceDifferenceAttribute(): ?float
    {
        if (!$this->submitted_price || !$this->winning_price) {
            return null;
        }
        return $this->submitted_price - $this->winning_price;
    }

    public function getPriceDifferencePercentageAttribute(): ?float
    {
        if (!$this->submitted_price || !$this->winning_price || $this->winning_price == 0) {
            return null;
        }
        return (($this->submitted_price - $this->winning_price) / $this->winning_price) * 100;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            TenderStatus::WON,
            TenderStatus::LOST,
            TenderStatus::CANCELLED,
            TenderStatus::NO_GO,
        ]);
    }

    public function scopeByStatus($query, TenderStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeUpcoming($query, int $days = 7)
    {
        return $query->where('submission_deadline', '>=', now())
            ->where('submission_deadline', '<=', now()->addDays($days));
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('created_at', now()->year);
    }

    // Methods
    public static function generateNumber(): string
    {
        $year = now()->year;
        $prefix = "TND-{$year}-";
        
        // البحث عن آخر رقم مستخدم
        $lastTender = static::withTrashed()
            ->where('tender_number', 'like', $prefix . '%')
            ->orderByRaw("CAST(SUBSTR(tender_number, -4) AS INTEGER) DESC")
            ->first();
        
        if ($lastTender) {
            $lastNumber = (int) substr($lastTender->tender_number, -4);
            $count = $lastNumber + 1;
        } else {
            $count = 1;
        }
        
        // التحقق من أن الرقم غير موجود
        $newNumber = sprintf('TND-%d-%04d', $year, $count);
        while (static::withTrashed()->where('tender_number', $newNumber)->exists()) {
            $count++;
            $newNumber = sprintf('TND-%d-%04d', $year, $count);
        }
        
        return $newNumber;
    }

    public function calculateTotalCost(): void
    {
        $directCost = $this->boqItems()->sum('total_amount') ?? 0;
        $overhead = $this->overheads()->sum('amount') ?? 0;
        
        $this->total_direct_cost = $directCost;
        $this->total_overhead = $overhead;
        $this->total_cost = $directCost + $overhead;
        
        if ($this->markup_percentage) {
            $this->markup_amount = $this->total_cost * ($this->markup_percentage / 100);
            $this->submitted_price = $this->total_cost + $this->markup_amount;
        }
        
        $this->save();
    }

    public function logActivity(string $type, string $title, ?string $description = null, ?string $oldValue = null, ?string $newValue = null): void
    {
        $this->activities()->create([
            'activity_type' => $type,
            'title' => $title,
            'description' => $description,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'user_id' => auth()->id(),
        ]);
    }

    protected static function booted(): void
    {
        static::creating(function (Tender $tender) {
            if (!$tender->tender_number) {
                $tender->tender_number = static::generateNumber();
            }
            $tender->created_by = auth()->id();
        });

        static::updating(function (Tender $tender) {
            $tender->updated_by = auth()->id();
        });

        // Use updated event for logging to avoid issues
        static::updated(function (Tender $tender) {
            // Log status changes
            if ($tender->wasChanged('status')) {
                try {
                    $oldStatus = $tender->getOriginal('status');
                    $oldStatusValue = $oldStatus instanceof \BackedEnum ? $oldStatus->value : ($oldStatus ?? 'غير محدد');
                    $newStatusValue = $tender->status instanceof \BackedEnum ? $tender->status->value : $tender->status;
                    
                    $tender->activities()->create([
                        'activity_type' => 'status_change',
                        'title' => 'تغيير الحالة',
                        'description' => 'تم تغيير حالة العطاء من ' . $oldStatusValue . ' إلى ' . $newStatusValue,
                        'old_value' => $oldStatusValue,
                        'new_value' => $newStatusValue,
                        'user_id' => auth()->id(),
                    ]);
                } catch (\Exception $e) {
                    // Silently fail if activity logging fails
                    \Log::warning('Failed to log tender activity: ' . $e->getMessage());
                }
            }
        });
    }
}
