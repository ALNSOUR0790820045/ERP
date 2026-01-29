<?php

namespace App\Models\Tenders;

use App\Models\Tender;
use App\Models\TenderBond;
use App\Models\User;
use App\Models\Projects\Project;
use App\Models\Contracts\Contract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تحويل العطاء إلى مشروع
 * Tender to Project Conversion
 */
class TenderToProjectConversion extends Model
{
    protected $fillable = [
        'tender_id',
        'project_id',
        'contract_id',
        'conversion_date',
        'converted_by',
        // بيانات المشروع
        'project_code',
        'project_name_ar',
        'project_name_en',
        // بيانات العقد
        'contract_number',
        'contract_date',
        'contract_value',
        'contract_duration_days',
        'expected_start_date',
        'expected_end_date',
        // كفالة حسن التنفيذ
        'performance_bond_id',
        'performance_bond_amount',
        'performance_bond_date',
        // الدفعة المقدمة
        'advance_payment_amount',
        'advance_payment_percentage',
        'advance_payment_bond_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'conversion_date' => 'date',
        'contract_date' => 'date',
        'contract_value' => 'decimal:3',
        'expected_start_date' => 'date',
        'expected_end_date' => 'date',
        'performance_bond_amount' => 'decimal:3',
        'performance_bond_date' => 'date',
        'advance_payment_amount' => 'decimal:3',
        'advance_payment_percentage' => 'decimal:2',
    ];

    // الحالات
    public const STATUSES = [
        'pending' => 'بانتظار التحويل',
        'contract_signing' => 'توقيع العقد',
        'project_setup' => 'إعداد المشروع',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي',
    ];

    // العلاقات
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function converter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    public function performanceBond(): BelongsTo
    {
        return $this->belongsTo(TenderBond::class, 'performance_bond_id');
    }

    public function advancePaymentBond(): BelongsTo
    {
        return $this->belongsTo(TenderBond::class, 'advance_payment_bond_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['contract_signing', 'project_setup']);
    }

    // Methods
    public function startContractSigning(): void
    {
        $this->update(['status' => 'contract_signing']);
    }

    public function startProjectSetup(): void
    {
        $this->update(['status' => 'project_setup']);
    }

    public function complete(): void
    {
        $this->update(['status' => 'completed']);

        // تحديث العطاء
        $this->tender->update([
            'converted_to_project' => true,
            'project_id' => $this->project_id,
            'contract_id' => $this->contract_id,
        ]);
    }

    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'notes' => $reason,
        ]);
    }

    public function createProject(): ?Project
    {
        if (!class_exists(Project::class)) {
            return null;
        }

        $project = Project::create([
            'code' => $this->project_code ?? $this->tender->tender_number,
            'name_ar' => $this->project_name_ar ?? $this->tender->name_ar,
            'name_en' => $this->project_name_en ?? $this->tender->name_en,
            'client_id' => $this->tender->owner_id,
            'client_name' => $this->tender->owner_name,
            'contract_value' => $this->contract_value,
            'start_date' => $this->expected_start_date,
            'end_date' => $this->expected_end_date,
            'status' => 'active',
            'source_tender_id' => $this->tender_id,
        ]);

        $this->update(['project_id' => $project->id]);

        return $project;
    }

    public function createContract(): ?Contract
    {
        if (!class_exists(Contract::class)) {
            return null;
        }

        $contract = Contract::create([
            'contract_number' => $this->contract_number,
            'contract_date' => $this->contract_date,
            'project_id' => $this->project_id,
            'client_id' => $this->tender->owner_id,
            'client_name' => $this->tender->owner_name,
            'contract_value' => $this->contract_value,
            'duration_days' => $this->contract_duration_days,
            'start_date' => $this->expected_start_date,
            'end_date' => $this->expected_end_date,
            'source_tender_id' => $this->tender_id,
        ]);

        $this->update(['contract_id' => $contract->id]);

        return $contract;
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getHasPerformanceBondAttribute(): bool
    {
        return !is_null($this->performance_bond_id);
    }

    public function getHasAdvancePaymentAttribute(): bool
    {
        return !is_null($this->advance_payment_amount) && $this->advance_payment_amount > 0;
    }
}
