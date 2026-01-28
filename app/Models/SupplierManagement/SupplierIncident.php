<?php

namespace App\Models\SupplierManagement;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierIncident extends Model
{
    protected $fillable = [
        'supplier_id', 'incident_code', 'incident_type', 'category', 'title', 'description',
        'severity', 'priority', 'status', 'reported_by', 'reported_date', 'assigned_to',
        'reference_type', 'reference_id', 'root_cause', 'root_cause_analysis',
        'immediate_action', 'corrective_action', 'preventive_action', 'cost_impact',
        'schedule_impact_days', 'quality_impact', 'resolution', 'resolution_date',
        'verified_by', 'verified_date', 'lessons_learned', 'attachments', 'metadata',
    ];

    protected $casts = [
        'reported_date' => 'date', 'resolution_date' => 'date', 'verified_date' => 'date',
        'cost_impact' => 'decimal:2', 'attachments' => 'array', 'metadata' => 'array',
    ];

    const TYPE_QUALITY = 'quality';
    const TYPE_DELIVERY = 'delivery';
    const TYPE_COMPLIANCE = 'compliance';
    const TYPE_SAFETY = 'safety';
    const TYPE_CONTRACTUAL = 'contractual';
    const TYPE_COMMUNICATION = 'communication';
    const TYPE_OTHER = 'other';

    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    const STATUS_REPORTED = 'reported';
    const STATUS_INVESTIGATING = 'investigating';
    const STATUS_ACTION_REQUIRED = 'action_required';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_VERIFIED = 'verified';
    const STATUS_CLOSED = 'closed';

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }

    public function scopeOpen($q) { return $q->whereNotIn('status', [self::STATUS_VERIFIED, self::STATUS_CLOSED]); }
    public function scopeCritical($q) { return $q->where('severity', self::SEVERITY_CRITICAL); }
    public function scopeByType($q, string $type) { return $q->where('incident_type', $type); }

    public function assign(User $user): void {
        $this->update(['assigned_to' => $user->id, 'status' => self::STATUS_INVESTIGATING]);
    }

    public function resolve(string $resolution, array $actions = []): void {
        $this->update([
            'status' => self::STATUS_RESOLVED, 'resolution' => $resolution,
            'resolution_date' => now(),
            'corrective_action' => $actions['corrective'] ?? $this->corrective_action,
            'preventive_action' => $actions['preventive'] ?? $this->preventive_action,
        ]);
    }

    public function verify(User $verifier, string $lessons = null): void {
        $this->update([
            'status' => self::STATUS_VERIFIED, 'verified_by' => $verifier->id,
            'verified_date' => now(), 'lessons_learned' => $lessons,
        ]);
    }

    public function close(): void {
        if ($this->status === self::STATUS_VERIFIED) {
            $this->update(['status' => self::STATUS_CLOSED]);
        }
    }

    public function escalate(string $reason): void {
        $severities = [self::SEVERITY_LOW, self::SEVERITY_MEDIUM, self::SEVERITY_HIGH, self::SEVERITY_CRITICAL];
        $currentIndex = array_search($this->severity, $severities);
        if ($currentIndex < count($severities) - 1) {
            $this->update([
                'severity' => $severities[$currentIndex + 1],
                'metadata' => array_merge($this->metadata ?? [], ['escalation_reason' => $reason, 'escalated_at' => now()->toDateTimeString()]),
            ]);
        }
    }

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->incident_code)) {
                $model->incident_code = 'SIN-' . date('Ymd') . '-' . str_pad(static::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
