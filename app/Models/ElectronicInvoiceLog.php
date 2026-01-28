<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectronicInvoiceLog extends Model
{
    protected $fillable = [
        'electronic_invoice_id',
        'action',
        'status_before',
        'status_after',
        'request_data',
        'response_data',
        'error_message',
        'ip_address',
        'performed_by',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    public static array $actionLabels = [
        'created' => 'تم الإنشاء',
        'signed' => 'تم التوقيع',
        'submitted' => 'تم الإرسال',
        'accepted' => 'تم القبول',
        'rejected' => 'تم الرفض',
        'cancelled' => 'تم الإلغاء',
        'retry' => 'إعادة المحاولة',
    ];

    public function electronicInvoice(): BelongsTo
    {
        return $this->belongsTo(ElectronicInvoice::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
