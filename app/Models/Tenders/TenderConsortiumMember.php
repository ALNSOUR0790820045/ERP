<?php

namespace App\Models\Tenders;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نموذج عضو الائتلاف
 * يمثل شركة عضو في ائتلاف مناقصين
 */
class TenderConsortiumMember extends Model
{
    protected $fillable = [
        'consortium_id',
        'company_id',
        'company_name',
        'classification',
        'share_percentage',
        'scope_of_work',
        'is_lead',
        'authorized_signatory',
        'signatory_title',
        'contact_person',
        'contact_phone',
        'contact_email',
        'sort_order',
    ];

    protected $casts = [
        'share_percentage' => 'decimal:2',
        'is_lead' => 'boolean',
    ];

    // ==========================================
    // العلاقات
    // ==========================================

    public function consortium(): BelongsTo
    {
        return $this->belongsTo(TenderConsortium::class, 'consortium_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ==========================================
    // Accessors
    // ==========================================

    /**
     * اسم الشركة
     */
    public function getNameAttribute(): string
    {
        return $this->company?->name ?? $this->company_name ?? 'غير محدد';
    }

    /**
     * الدور في الائتلاف
     */
    public function getRoleAttribute(): string
    {
        return $this->is_lead ? 'رئيس الائتلاف' : 'عضو';
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeLead($query)
    {
        return $query->where('is_lead', true);
    }

    public function scopeMembers($query)
    {
        return $query->where('is_lead', false);
    }

    // ==========================================
    // Methods
    // ==========================================

    /**
     * تعيين كرئيس للائتلاف
     */
    public function setAsLead(): void
    {
        // إزالة الرئاسة من الأعضاء الآخرين
        $this->consortium->members()->where('id', '!=', $this->id)->update(['is_lead' => false]);
        
        // تعيين هذا العضو كرئيس
        $this->update(['is_lead' => true]);
        
        // تحديث الائتلاف
        $this->consortium->update([
            'lead_company_id' => $this->company_id,
            'lead_company_name' => $this->company_name,
        ]);
    }
}
