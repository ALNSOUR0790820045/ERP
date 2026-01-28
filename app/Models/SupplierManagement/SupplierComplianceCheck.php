<?php

namespace App\Models\SupplierManagement;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierComplianceCheck extends Model
{
    protected $fillable = [
        'supplier_id', 'check_code', 'check_type', 'check_category', 'title',
        'description', 'requirement', 'check_date', 'checked_by', 'status',
        'result', 'findings', 'evidence', 'corrective_action_required',
        'corrective_action', 'due_date', 'resolved_date', 'next_check_date', 'metadata',
    ];

    protected $casts = [
        'check_date' => 'date', 'due_date' => 'date', 'resolved_date' => 'date',
        'next_check_date' => 'date', 'findings' => 'array', 'evidence' => 'array',
        'corrective_action_required' => 'boolean', 'metadata' => 'array',
    ];

    const TYPE_REGULATORY = 'regulatory';
    const TYPE_CONTRACTUAL = 'contractual';
    const TYPE_QUALITY = 'quality';
    const TYPE_ENVIRONMENTAL = 'environmental';
    const TYPE_SAFETY = 'safety';
    const TYPE_ETHICAL = 'ethical';

    const RESULT_COMPLIANT = 'compliant';
    const RESULT_MINOR_NC = 'minor_non_compliance';
    const RESULT_MAJOR_NC = 'major_non_compliance';
    const RESULT_CRITICAL_NC = 'critical_non_compliance';

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function checker(): BelongsTo { return $this->belongsTo(User::class, 'checked_by'); }

    public function scopeCompliant($q) { return $q->where('result', self::RESULT_COMPLIANT); }
    public function scopeNonCompliant($q) { return $q->where('result', '!=', self::RESULT_COMPLIANT); }
    public function scopePendingAction($q) { return $q->where('corrective_action_required', true)->whereNull('resolved_date'); }
    public function scopeOverdue($q) { return $q->where('due_date', '<', now())->whereNull('resolved_date'); }

    public function markCompliant(): void {
        $this->update(['result' => self::RESULT_COMPLIANT, 'corrective_action_required' => false]);
    }

    public function markNonCompliant(string $level, string $action, \DateTime $dueDate): void {
        $this->update([
            'result' => $level, 'corrective_action_required' => true,
            'corrective_action' => $action, 'due_date' => $dueDate,
        ]);
    }

    public function resolve(string $resolution): void {
        $this->update(['resolved_date' => now(), 'status' => 'resolved', 
            'metadata' => array_merge($this->metadata ?? [], ['resolution' => $resolution])]);
    }

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->check_code)) {
                $model->check_code = 'SCC-' . date('Ymd') . '-' . str_pad(static::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
