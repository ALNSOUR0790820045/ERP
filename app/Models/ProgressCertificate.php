<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgressCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'project_id',
        'certificate_number',
        'ipc_number',
        'period_from',
        'period_to',
        'submission_date',
        'approval_date',
        
        // المبالغ
        'cumulative_work_done',
        'previous_work_done',
        'current_work_done',
        'materials_on_site',
        'previous_materials',
        'current_materials',
        'gross_amount',
        
        // الحسميات
        'retention_rate',
        'retention_amount',
        'previous_retention',
        'current_retention',
        'advance_recovery',
        'previous_advance_recovery',
        'current_advance_recovery',
        'other_deductions',
        'total_deductions',
        
        // صافي المبلغ
        'net_amount',
        'previous_net',
        'current_net',
        
        // الضريبة
        'vat_rate',
        'vat_amount',
        'final_amount',
        
        'status',
        'prepared_by',
        'checked_by',
        'approved_by',
        'payment_voucher_id',
        'notes',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'submission_date' => 'date',
        'approval_date' => 'date',
        'cumulative_work_done' => 'decimal:3',
        'previous_work_done' => 'decimal:3',
        'current_work_done' => 'decimal:3',
        'materials_on_site' => 'decimal:3',
        'previous_materials' => 'decimal:3',
        'current_materials' => 'decimal:3',
        'gross_amount' => 'decimal:3',
        'retention_rate' => 'decimal:2',
        'retention_amount' => 'decimal:3',
        'previous_retention' => 'decimal:3',
        'current_retention' => 'decimal:3',
        'advance_recovery' => 'decimal:3',
        'previous_advance_recovery' => 'decimal:3',
        'current_advance_recovery' => 'decimal:3',
        'other_deductions' => 'decimal:3',
        'total_deductions' => 'decimal:3',
        'net_amount' => 'decimal:3',
        'previous_net' => 'decimal:3',
        'current_net' => 'decimal:3',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:3',
        'final_amount' => 'decimal:3',
    ];

    // العلاقات
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProgressCertificateItem::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProgressCertificateMaterial::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paymentVoucher(): BelongsTo
    {
        return $this->belongsTo(PaymentVoucher::class);
    }

    public function advanceRecoveries(): HasMany
    {
        return $this->hasMany(ContractAdvanceRecovery::class);
    }

    // الثوابت
    public const STATUSES = [
        'draft' => 'مسودة',
        'submitted' => 'مقدم',
        'under_review' => 'تحت المراجعة',
        'checked' => 'تم التدقيق',
        'approved' => 'معتمد',
        'paid' => 'مدفوع',
        'rejected' => 'مرفوض',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * حساب المستخلص
     */
    public function calculate(): void
    {
        // المبالغ الحالية
        $this->current_work_done = $this->cumulative_work_done - $this->previous_work_done;
        $this->current_materials = $this->materials_on_site - $this->previous_materials;
        $this->gross_amount = $this->cumulative_work_done + $this->materials_on_site;

        // المحتجز
        $this->retention_amount = $this->cumulative_work_done * ($this->retention_rate / 100);
        $this->current_retention = $this->retention_amount - $this->previous_retention;

        // استرداد الدفعة المقدمة
        $this->current_advance_recovery = $this->advance_recovery - $this->previous_advance_recovery;

        // إجمالي الحسميات
        $this->total_deductions = $this->retention_amount + $this->advance_recovery + $this->other_deductions;

        // صافي المبلغ
        $this->net_amount = $this->gross_amount - $this->total_deductions;
        $this->current_net = $this->net_amount - $this->previous_net;

        // الضريبة
        $this->vat_amount = $this->current_net * ($this->vat_rate / 100);
        $this->final_amount = $this->current_net + $this->vat_amount;
    }
}
