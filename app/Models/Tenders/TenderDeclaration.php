<?php

namespace App\Models\Tenders;

use App\Models\Company;
use App\Models\Tender;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نموذج الإقرارات
 * يمثل الإقرارات المطلوبة من المناقصين حسب الوثائق القياسية الأردنية
 */
class TenderDeclaration extends Model
{
    protected $fillable = [
        'tender_id',
        'bidder_id',
        'bidder_name',
        'declaration_type',
        'declaration_title',
        'declaration_text',
        'is_signed',
        'signatory_name',
        'signatory_title',
        'signature_date',
        'signature_file',
        'is_required',
        'status',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'is_signed' => 'boolean',
        'is_required' => 'boolean',
        'signature_date' => 'date',
        'verified_at' => 'datetime',
    ];

    // ==========================================
    // الثوابت - أنواع الإقرارات
    // ==========================================

    const TYPE_ESMP_COMMITMENT = 'esmp_commitment';
    const TYPE_CODE_OF_CONDUCT = 'code_of_conduct';
    const TYPE_OTHER_PAYMENTS = 'other_payments';
    const TYPE_PROHIBITED_PAYMENTS = 'prohibited_payments';
    const TYPE_NO_CONFLICT = 'no_conflict_of_interest';
    const TYPE_ANTI_CORRUPTION = 'anti_corruption';
    const TYPE_ELIGIBILITY = 'eligibility';
    const TYPE_VALIDITY_ACCEPTANCE = 'validity_acceptance';

    /**
     * الحصول على عناوين الإقرارات
     */
    public static function getDeclarationTypes(): array
    {
        return [
            self::TYPE_ESMP_COMMITMENT => 'إقرار الالتزام بتنفيذ خطة الإدارة البيئية والاجتماعية',
            self::TYPE_CODE_OF_CONDUCT => 'مدونة قواعد السلوك لمستخدمي المقاول',
            self::TYPE_OTHER_PAYMENTS => 'إقرار الدفعات الأخرى',
            self::TYPE_PROHIBITED_PAYMENTS => 'إقرار الدفعات الممنوعة',
            self::TYPE_NO_CONFLICT => 'إقرار عدم تضارب المصالح',
            self::TYPE_ANTI_CORRUPTION => 'إقرار مكافحة الفساد والاحتيال',
            self::TYPE_ELIGIBILITY => 'إقرار الأهلية',
            self::TYPE_VALIDITY_ACCEPTANCE => 'إقرار قبول فترة صلاحية العرض',
        ];
    }

    /**
     * النص الافتراضي لكل نوع إقرار
     */
    public static function getDefaultText(string $type): string
    {
        return match($type) {
            self::TYPE_ESMP_COMMITMENT => 'أتعهد بالالتزام التام بتنفيذ خطة الإدارة البيئية والاجتماعية (ESMP) المرفقة مع وثائق المناقصة، وأن أتحمل كامل المسؤولية عن أي مخالفة للمتطلبات البيئية والاجتماعية.',
            
            self::TYPE_CODE_OF_CONDUCT => 'أتعهد بإلزام جميع موظفي الشركة والمقاولين الفرعيين بالتقيد بمدونة قواعد السلوك المرفقة، والتي تتضمن معايير السلوك المهني والأخلاقي وحماية البيئة والصحة والسلامة.',
            
            self::TYPE_OTHER_PAYMENTS => 'أقر بأنني سأفصح عن جميع الدفعات الأخرى المتعلقة بهذا العرض، بما في ذلك أي عمولات أو رسوم أو مدفوعات لأي طرف ثالث.',
            
            self::TYPE_PROHIBITED_PAYMENTS => 'أقر بأنني لم أقدم ولن أقدم أي رشوة أو مدفوعات غير مشروعة لأي موظف حكومي أو أي شخص آخر بهدف التأثير على نتيجة هذه المناقصة.',
            
            self::TYPE_NO_CONFLICT => 'أقر بأنه لا يوجد تضارب في المصالح بيني وبين الجهة المشترية أو أي من موظفيها أو أعضاء لجنة الشراء، وأنني لست تحت إدارة مشتركة مع أي مناقص آخر.',
            
            self::TYPE_ANTI_CORRUPTION => 'أتعهد بالامتناع عن أي ممارسات تنطوي على فساد أو احتيال أو تواطؤ أو إكراه أو إعاقة، وأقر بعلمي بالعقوبات المترتبة على مخالفة ذلك.',
            
            self::TYPE_ELIGIBILITY => 'أقر بأن الشركة مستوفية لجميع شروط الأهلية المحددة في وثائق المناقصة، وأن جميع المعلومات المقدمة صحيحة ودقيقة.',
            
            self::TYPE_VALIDITY_ACCEPTANCE => 'أقر بقبول إبقاء العرض ساري المفعول للفترة المحددة في وثائق المناقصة، وأتعهد بعدم سحبه أو تعديله خلال هذه الفترة.',
            
            default => '',
        };
    }

    // ==========================================
    // العلاقات
    // ==========================================

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function bidder(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'bidder_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ==========================================
    // Accessors
    // ==========================================

    /**
     * اسم المناقص
     */
    public function getBidderDisplayNameAttribute(): string
    {
        return $this->bidder?->name ?? $this->bidder_name ?? 'غير محدد';
    }

    /**
     * نوع الإقرار بالعربية
     */
    public function getTypeArabicAttribute(): string
    {
        return self::getDeclarationTypes()[$this->declaration_type] ?? 'غير محدد';
    }

    /**
     * حالة الإقرار بالعربية
     */
    public function getStatusArabicAttribute(): string
    {
        return match($this->status) {
            'pending' => 'قيد الانتظار',
            'submitted' => 'مقدم',
            'verified' => 'تم التحقق',
            'rejected' => 'مرفوض',
            default => 'غير محدد',
        };
    }

    /**
     * لون الحالة
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'submitted' => 'warning',
            'verified' => 'success',
            'rejected' => 'danger',
            default => 'gray',
        };
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('declaration_type', $type);
    }

    public function scopeByBidder($query, int $bidderId)
    {
        return $query->where('bidder_id', $bidderId);
    }

    // ==========================================
    // Methods
    // ==========================================

    /**
     * توقيع الإقرار
     */
    public function sign(string $signatoryName, string $signatoryTitle, ?string $signatureFile = null): void
    {
        $this->update([
            'is_signed' => true,
            'signatory_name' => $signatoryName,
            'signatory_title' => $signatoryTitle,
            'signature_date' => now(),
            'signature_file' => $signatureFile,
            'status' => 'submitted',
        ]);
    }

    /**
     * التحقق من الإقرار
     */
    public function verify(int $userId): void
    {
        $this->update([
            'status' => 'verified',
            'verified_by' => $userId,
            'verified_at' => now(),
        ]);
    }

    /**
     * رفض الإقرار
     */
    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * إنشاء الإقرارات الإلزامية للعطاء
     */
    public static function createRequiredDeclarations(Tender $tender, int $bidderId, string $bidderName): array
    {
        $declarations = [];
        
        foreach (self::getDeclarationTypes() as $type => $title) {
            $declarations[] = self::create([
                'tender_id' => $tender->id,
                'bidder_id' => $bidderId,
                'bidder_name' => $bidderName,
                'declaration_type' => $type,
                'declaration_title' => $title,
                'declaration_text' => self::getDefaultText($type),
                'is_required' => true,
                'status' => 'pending',
            ]);
        }
        
        return $declarations;
    }
}
