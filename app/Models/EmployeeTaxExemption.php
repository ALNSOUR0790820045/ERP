<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeTaxExemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'year',
        'exemption_type',
        'amount',
        'document_number',
        'document_date',
        'notes',
        'is_approved',
        'approved_by',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'document_date' => 'date',
        'is_approved' => 'boolean',
    ];

    // العلاقات
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // الثوابت
    public const EXEMPTION_TYPES = [
        'personal' => 'إعفاء شخصي',
        'spouse' => 'إعفاء الزوج/الزوجة',
        'children' => 'إعفاء الأولاد',
        'education' => 'نفقات تعليم',
        'health' => 'نفقات صحية',
        'housing' => 'فوائد قروض الإسكان',
        'donations' => 'تبرعات',
    ];

    public function getExemptionTypeLabelAttribute(): string
    {
        return self::EXEMPTION_TYPES[$this->exemption_type] ?? $this->exemption_type;
    }
}
