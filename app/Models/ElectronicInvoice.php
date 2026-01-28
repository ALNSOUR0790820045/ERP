<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * الفاتورة الإلكترونية
 * Electronic Invoice for JoFotara
 */
class ElectronicInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'einvoice_number',
        'uuid',
        'qr_code',
        'qr_code_data',
        'status',
        'digital_signature',
        'signature_hash',
        'signed_at',
        'jofotara_reference',
        'submission_response',
        'rejection_reason',
        'retry_count',
        'submitted_at',
        'accepted_at',
        'xml_content',
        'json_payload',
    ];

    protected $casts = [
        'submission_response' => 'array',
        'signed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'accepted_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ElectronicInvoiceLog::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeNeedsRetry($query)
    {
        return $query->where('status', 'rejected')
            ->where('retry_count', '<', 3);
    }

    // Status Labels
    public static array $statusLabels = [
        'draft' => 'مسودة',
        'pending' => 'في انتظار الإرسال',
        'submitted' => 'تم الإرسال',
        'accepted' => 'مقبول',
        'rejected' => 'مرفوض',
        'cancelled' => 'ملغي',
    ];

    public static array $statusColors = [
        'draft' => 'gray',
        'pending' => 'warning',
        'submitted' => 'info',
        'accepted' => 'success',
        'rejected' => 'danger',
        'cancelled' => 'gray',
    ];

    // Methods
    public function logAction(string $action, ?array $request = null, ?array $response = null, ?string $error = null, ?int $userId = null): void
    {
        $this->logs()->create([
            'action' => $action,
            'status_before' => $this->getOriginal('status'),
            'status_after' => $this->status,
            'request_data' => $request,
            'response_data' => $response,
            'error_message' => $error,
            'ip_address' => request()->ip(),
            'performed_by' => $userId ?? auth()->id(),
        ]);
    }

    public function markAsSigned(string $signature, string $hash): bool
    {
        $previousStatus = $this->status;
        
        $this->digital_signature = $signature;
        $this->signature_hash = $hash;
        $this->signed_at = now();
        $this->status = 'pending';
        $result = $this->save();
        
        $this->logAction('signed');
        
        return $result;
    }

    public function markAsSubmitted(string $reference, array $response): bool
    {
        $this->jofotara_reference = $reference;
        $this->submission_response = $response;
        $this->submitted_at = now();
        $this->status = 'submitted';
        $result = $this->save();
        
        $this->logAction('submitted', null, $response);
        
        return $result;
    }

    public function markAsAccepted(string $einvoiceNumber, ?string $qrCode = null): bool
    {
        $this->einvoice_number = $einvoiceNumber;
        $this->qr_code = $qrCode;
        $this->accepted_at = now();
        $this->status = 'accepted';
        $result = $this->save();
        
        $this->logAction('accepted');
        
        return $result;
    }

    public function markAsRejected(string $reason, array $response): bool
    {
        $this->rejection_reason = $reason;
        $this->submission_response = $response;
        $this->retry_count++;
        $this->status = 'rejected';
        $result = $this->save();
        
        $this->logAction('rejected', null, $response, $reason);
        
        return $result;
    }

    public function cancel(): bool
    {
        $this->status = 'cancelled';
        $result = $this->save();
        
        $this->logAction('cancelled');
        
        return $result;
    }

    public function canRetry(): bool
    {
        return $this->status === 'rejected' && $this->retry_count < 3;
    }

    public function retry(): bool
    {
        if (!$this->canRetry()) return false;
        
        $this->status = 'pending';
        $result = $this->save();
        
        $this->logAction('retry');
        
        return $result;
    }

    public function generateQRCodeData(): string
    {
        $invoice = $this->invoice;
        $settings = JofotaraSetting::getActive();
        
        $data = [
            'seller_name' => config('app.company_name'),
            'seller_tax_id' => $settings?->taxpayer_id,
            'invoice_date' => $invoice->invoice_date->format('Y-m-d H:i:s'),
            'invoice_total' => $invoice->total_amount,
            'tax_total' => $invoice->tax_amount,
        ];
        
        return base64_encode(json_encode($data));
    }
}
