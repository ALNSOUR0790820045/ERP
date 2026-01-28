<?php

namespace App\Models\SupplierManagement;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierAudit extends Model
{
    protected $fillable = [
        'supplier_id', 'audit_code', 'audit_type', 'audit_category', 'title', 'description',
        'scheduled_date', 'start_date', 'end_date', 'auditor_id', 'lead_auditor', 'audit_team',
        'status', 'scope', 'checklist', 'findings', 'non_conformities', 'observations',
        'corrective_actions', 'audit_score', 'audit_result', 'report_date', 'next_audit_date',
        'approved_by', 'approved_at', 'metadata',
    ];

    protected $casts = [
        'scheduled_date' => 'date', 'start_date' => 'date', 'end_date' => 'date',
        'report_date' => 'date', 'next_audit_date' => 'date', 'approved_at' => 'datetime',
        'audit_team' => 'array', 'scope' => 'array', 'checklist' => 'array',
        'findings' => 'array', 'non_conformities' => 'array', 'observations' => 'array',
        'corrective_actions' => 'array', 'audit_score' => 'decimal:2', 'metadata' => 'array',
    ];

    const TYPE_QUALITY = 'quality';
    const TYPE_FINANCIAL = 'financial';
    const TYPE_COMPLIANCE = 'compliance';
    const TYPE_ENVIRONMENTAL = 'environmental';
    const TYPE_SAFETY = 'safety';
    const TYPE_SOCIAL = 'social';

    const RESULT_PASS = 'pass';
    const RESULT_CONDITIONAL = 'conditional';
    const RESULT_FAIL = 'fail';

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function auditor(): BelongsTo { return $this->belongsTo(User::class, 'auditor_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeScheduled($q) { return $q->where('status', 'scheduled'); }
    public function scopeInProgress($q) { return $q->where('status', 'in_progress'); }
    public function scopeCompleted($q) { return $q->where('status', 'completed'); }

    public function start(): void {
        $this->update(['status' => 'in_progress', 'start_date' => now()]);
    }

    public function complete(array $findings, string $result, float $score): void {
        $this->update([
            'status' => 'completed', 'end_date' => now(), 'report_date' => now(),
            'findings' => $findings, 'audit_result' => $result, 'audit_score' => $score,
        ]);
    }

    public function addFinding(string $type, string $description, string $severity): void {
        $findings = $this->findings ?? [];
        $findings[] = ['type' => $type, 'description' => $description, 'severity' => $severity, 'date' => now()->toDateString()];
        $this->update(['findings' => $findings]);
    }

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->audit_code)) {
                $model->audit_code = 'SAU-' . date('Ymd') . '-' . str_pad(static::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
