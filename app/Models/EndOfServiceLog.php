<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سجل تغييرات نهاية الخدمة
 * End of Service Logs
 */
class EndOfServiceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'calculation_id',
        'action',
        'old_values',
        'new_values',
        'notes',
        'performed_by',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // العلاقات
    public function calculation(): BelongsTo
    {
        return $this->belongsTo(EndOfServiceCalculation::class, 'calculation_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // Accessors
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'created' => 'تم الإنشاء',
            'updated' => 'تم التحديث',
            'approved' => 'تمت الموافقة',
            'rejected' => 'تم الرفض',
            'paid' => 'تم الدفع',
            'cancelled' => 'تم الإلغاء',
            default => $this->action,
        };
    }
}
