<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تقديم العطاء
 * Tender Submission - بيانات تسليم المظاريف
 */
class TenderSubmission extends Model
{
    protected $fillable = [
        'tender_id',
        'submission_datetime',
        'submission_method',
        'delivered_by',
        'delivery_id_number',
        'receiver_name',
        'receipt_number',
        'receipt_path',
        'tracking_number',
        'courier_name',
        'submission_reference',
        'confirmation_number',
        'original_copies',
        'additional_copies',
        'technical_separate',
        'financial_separate',
        'envelope_contents',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'submission_datetime' => 'datetime',
        'original_copies' => 'integer',
        'additional_copies' => 'integer',
        'technical_separate' => 'boolean',
        'financial_separate' => 'boolean',
        'envelope_contents' => 'array',
    ];

    // طرق التقديم
    public const METHODS = [
        'hand_delivery' => 'تسليم يدوي',
        'registered_mail' => 'بريد مسجل',
        'electronic' => 'إلكتروني',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getMethodNameAttribute(): string
    {
        return self::METHODS[$this->submission_method] ?? $this->submission_method;
    }

    /**
     * إجمالي عدد النسخ
     */
    public function getTotalCopiesAttribute(): int
    {
        return $this->original_copies + $this->additional_copies;
    }
}
