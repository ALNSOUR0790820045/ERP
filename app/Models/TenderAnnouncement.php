<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * إعلانات العطاءات
 * Tender Announcements - الدعوة للمناقصة
 */
class TenderAnnouncement extends Model
{
    protected $fillable = [
        'tender_id',
        'announcement_type',
        'announcement_number',
        'announcement_date',
        'publication_channels',
        'publication_date',
        'publication_reference',
        'title_ar',
        'title_en',
        'content_ar',
        'content_en',
        'attachment_path',
        'status',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'announcement_date' => 'date',
        'publication_date' => 'date',
        'publication_channels' => 'array',
        'published_at' => 'datetime',
    ];

    // أنواع الإعلانات
    public const TYPES = [
        'invitation' => 'دعوة للمناقصة',
        'addendum' => 'ملحق',
        'clarification' => 'توضيح',
        'cancellation' => 'إلغاء',
        'extension' => 'تمديد',
        'result' => 'نتيجة',
    ];

    // قنوات النشر
    public const CHANNELS = [
        'official_gazette' => 'الجريدة الرسمية',
        'al_rai' => 'صحيفة الرأي',
        'doustour' => 'صحيفة الدستور',
        'gtd_website' => 'موقع دائرة العطاءات',
        'ministry_website' => 'موقع الوزارة',
        'company_website' => 'موقع الشركة',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->announcement_type] ?? $this->announcement_type;
    }
}
