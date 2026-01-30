<?php

namespace App\Models\Tenders;

use App\Models\Company;
use App\Models\Tender;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * نموذج الائتلاف
 * يمثل ائتلاف المناقصين المشتركين في عطاء
 */
class TenderConsortium extends Model
{
    // تحديد اسم الجدول صراحة لتجنب مشكلة الجمع اللاتيني
    protected $table = 'tender_consortiums';

    protected $fillable = [
        'tender_id',
        'consortium_name',
        'lead_company_id',
        'lead_company_name',
        'agreement_type',
        'agreement_date',
        'agreement_file',
        'is_certified',
        'certification_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'agreement_date' => 'date',
        'certification_date' => 'date',
        'is_certified' => 'boolean',
    ];

    // ==========================================
    // العلاقات
    // ==========================================

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function leadCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'lead_company_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TenderConsortiumMember::class, 'consortium_id')->orderBy('sort_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==========================================
    // Accessors
    // ==========================================

    /**
     * اسم رئيس الائتلاف
     */
    public function getLeadNameAttribute(): string
    {
        return $this->leadCompany?->name ?? $this->lead_company_name ?? 'غير محدد';
    }

    /**
     * عدد أعضاء الائتلاف
     */
    public function getMembersCountAttribute(): int
    {
        return $this->members()->count();
    }

    /**
     * هل الاتفاقية كاملة؟
     */
    public function getIsCompleteAttribute(): bool
    {
        return $this->agreement_type === 'full_agreement' 
            && $this->is_certified 
            && $this->agreement_file;
    }

    /**
     * حالة الاتفاقية بالعربية
     */
    public function getAgreementTypeArabicAttribute(): string
    {
        return match($this->agreement_type) {
            'full_agreement' => 'اتفاقية ائتلاف مصدقة',
            'letter_of_intent' => 'رسالة نوايا',
            default => 'غير محدد',
        };
    }

    /**
     * حالة الائتلاف بالعربية
     */
    public function getStatusArabicAttribute(): string
    {
        return match($this->status) {
            'draft' => 'مسودة',
            'pending' => 'قيد المراجعة',
            'approved' => 'معتمد',
            'rejected' => 'مرفوض',
            default => 'غير محدد',
        };
    }

    // ==========================================
    // Methods
    // ==========================================

    /**
     * إضافة عضو للائتلاف
     */
    public function addMember(array $data): TenderConsortiumMember
    {
        $data['consortium_id'] = $this->id;
        $data['sort_order'] = $this->members()->max('sort_order') + 1;
        
        return TenderConsortiumMember::create($data);
    }

    /**
     * إجمالي نسب المشاركة
     */
    public function getTotalSharePercentage(): float
    {
        return $this->members()->sum('share_percentage') ?? 0;
    }

    /**
     * التحقق من صحة نسب المشاركة (يجب أن تساوي 100%)
     */
    public function validateSharePercentages(): bool
    {
        return abs($this->getTotalSharePercentage() - 100) < 0.01;
    }

    /**
     * اعتماد الائتلاف
     */
    public function approve(): void
    {
        $this->update(['status' => 'approved']);
    }

    /**
     * رفض الائتلاف
     */
    public function reject(string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'notes' => $reason ?? $this->notes,
        ]);
    }

    /**
     * تحويل رسالة النوايا إلى اتفاقية كاملة
     */
    public function convertToFullAgreement(string $filePath, \DateTime $certificationDate): void
    {
        $this->update([
            'agreement_type' => 'full_agreement',
            'agreement_file' => $filePath,
            'is_certified' => true,
            'certification_date' => $certificationDate,
        ]);
    }
}
