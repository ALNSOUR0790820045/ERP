<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderActivity extends Model
{
    protected $fillable = [
        'tender_id',
        'activity_type',
        'description',
        'from_status',
        'to_status',
        'performed_by',
        'performed_at',
        'details',
        'ip_address',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
        'details' => 'array',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function getActivityTypeLabel(): string
    {
        return match($this->activity_type) {
            'created' => 'إنشاء',
            'updated' => 'تحديث',
            'status_changed' => 'تغيير حالة',
            'document_added' => 'إضافة مستند',
            'document_removed' => 'حذف مستند',
            'pricing_updated' => 'تحديث التسعير',
            'submitted' => 'تقديم',
            'result_recorded' => 'تسجيل نتيجة',
            'decision_made' => 'اتخاذ قرار',
            'comment_added' => 'إضافة تعليق',
            default => $this->activity_type,
        };
    }

    public function getIcon(): string
    {
        return match($this->activity_type) {
            'created' => 'heroicon-o-plus-circle',
            'updated' => 'heroicon-o-pencil',
            'status_changed' => 'heroicon-o-arrow-path',
            'document_added' => 'heroicon-o-document-plus',
            'document_removed' => 'heroicon-o-document-minus',
            'pricing_updated' => 'heroicon-o-calculator',
            'submitted' => 'heroicon-o-paper-airplane',
            'result_recorded' => 'heroicon-o-flag',
            'decision_made' => 'heroicon-o-check-badge',
            'comment_added' => 'heroicon-o-chat-bubble-left',
            default => 'heroicon-o-information-circle',
        };
    }

    public static function log(
        Tender $tender,
        string $type,
        string $description,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        array $details = []
    ): self {
        return self::create([
            'tender_id' => $tender->id,
            'activity_type' => $type,
            'description' => $description,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'performed_by' => auth()->id(),
            'performed_at' => now(),
            'details' => $details,
            'ip_address' => request()->ip(),
        ]);
    }
}
