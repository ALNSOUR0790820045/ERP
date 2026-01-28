<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ملحقات العطاء
 * Tender Addenda - التعديلات والملحقات
 */
class TenderAddendum extends Model
{
    protected $table = 'tender_addenda';

    protected $fillable = [
        'tender_id',
        'addendum_number',
        'issue_date',
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
        'modifies_boq',
        'modifies_drawings',
        'modifies_specifications',
        'modifies_conditions',
        'extends_deadline',
        'original_deadline',
        'new_deadline',
        'document_path',
        'affected_items',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'modifies_boq' => 'boolean',
        'modifies_drawings' => 'boolean',
        'modifies_specifications' => 'boolean',
        'modifies_conditions' => 'boolean',
        'extends_deadline' => 'boolean',
        'original_deadline' => 'datetime',
        'new_deadline' => 'datetime',
        'affected_items' => 'array',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    /**
     * ملخص التعديلات
     */
    public function getModificationsSummaryAttribute(): array
    {
        $modifications = [];
        
        if ($this->modifies_boq) {
            $modifications[] = 'جدول الكميات';
        }
        if ($this->modifies_drawings) {
            $modifications[] = 'الرسومات';
        }
        if ($this->modifies_specifications) {
            $modifications[] = 'المواصفات';
        }
        if ($this->modifies_conditions) {
            $modifications[] = 'الشروط';
        }
        if ($this->extends_deadline) {
            $modifications[] = 'الموعد النهائي';
        }
        
        return $modifications;
    }

    /**
     * عدد أيام التمديد
     */
    public function getExtensionDaysAttribute(): ?int
    {
        if (!$this->extends_deadline || !$this->original_deadline || !$this->new_deadline) {
            return null;
        }
        return $this->original_deadline->diffInDays($this->new_deadline);
    }
}
