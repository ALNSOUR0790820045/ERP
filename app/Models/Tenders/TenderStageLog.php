<?php

namespace App\Models\Tenders;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سجل مراحل العطاء
 * Tender Stage Log
 */
class TenderStageLog extends Model
{
    protected $fillable = [
        'tender_id',
        'stage',
        'status',
        'started_at',
        'completed_at',
        'completed_by',
        'notes',
        'stage_order',
        'is_mandatory',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_mandatory' => 'boolean',
    ];

    // المراحل
    public const STAGES = [
        'discovery' => ['label' => 'رصد المناقصات', 'order' => 1, 'mandatory' => true],
        'purchase_approval' => ['label' => 'موافقة الشراء', 'order' => 2, 'mandatory' => true],
        'documents_purchase' => ['label' => 'شراء الوثائق', 'order' => 3, 'mandatory' => true],
        'documents_entry' => ['label' => 'إدخال الوثائق', 'order' => 4, 'mandatory' => true],
        'site_visit' => ['label' => 'زيارة الموقع', 'order' => 5, 'mandatory' => false],
        'evaluation' => ['label' => 'تقييم المشروع', 'order' => 6, 'mandatory' => true],
        'go_no_go_decision' => ['label' => 'قرار المشاركة', 'order' => 7, 'mandatory' => true],
        'pricing' => ['label' => 'التسعير', 'order' => 8, 'mandatory' => true],
        'addenda_entry' => ['label' => 'إدخال الملاحق', 'order' => 9, 'mandatory' => false],
        'technical_preparation' => ['label' => 'تجهيز العرض الفني', 'order' => 10, 'mandatory' => true],
        'financial_preparation' => ['label' => 'تجهيز العرض المالي', 'order' => 11, 'mandatory' => true],
        'bonds_preparation' => ['label' => 'تجهيز الكفالات', 'order' => 12, 'mandatory' => true],
        'proposal_closure' => ['label' => 'إغلاق العرض', 'order' => 13, 'mandatory' => true],
        'submission' => ['label' => 'تقديم العرض', 'order' => 14, 'mandatory' => true],
        'technical_opening' => ['label' => 'فتح الفني', 'order' => 15, 'mandatory' => false],
        'financial_opening' => ['label' => 'فتح المالي', 'order' => 16, 'mandatory' => true],
        'results_entry' => ['label' => 'إدخال النتائج', 'order' => 17, 'mandatory' => true],
        'award_waiting' => ['label' => 'انتظار الإحالة', 'order' => 18, 'mandatory' => true],
        'performance_bond' => ['label' => 'كفالة التنفيذ', 'order' => 19, 'mandatory' => false],
        'bid_bond_withdrawal' => ['label' => 'سحب كفالة الدخول', 'order' => 20, 'mandatory' => true],
        'project_conversion' => ['label' => 'تحويل لمشروع', 'order' => 21, 'mandatory' => false],
        'archived' => ['label' => 'أرشفة', 'order' => 22, 'mandatory' => true],
    ];

    // حالات المرحلة
    public const STATUSES = [
        'not_started' => 'لم يبدأ',
        'in_progress' => 'جاري',
        'completed' => 'مكتمل',
        'skipped' => 'تم تخطيه',
        'failed' => 'فشل',
    ];

    // العلاقات
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    // Scopes
    public function scopeByStage($query, $stage)
    {
        return $query->where('stage', $stage);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'not_started');
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    // Methods
    public function start(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function complete(User $user, ?string $notes = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => $user->id,
            'notes' => $notes,
        ]);

        // تحديث المرحلة الحالية للعطاء
        $this->updateTenderCurrentStage();
    }

    public function skip(?string $reason = null): void
    {
        if ($this->is_mandatory) {
            throw new \Exception('لا يمكن تخطي مرحلة إلزامية');
        }

        $this->update([
            'status' => 'skipped',
            'notes' => $reason,
        ]);
    }

    public function fail(?string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'notes' => $reason,
        ]);
    }

    protected function updateTenderCurrentStage(): void
    {
        $nextStage = TenderStageLog::where('tender_id', $this->tender_id)
            ->where('status', 'not_started')
            ->orderBy('stage_order')
            ->first();

        $completedCount = TenderStageLog::where('tender_id', $this->tender_id)
            ->whereIn('status', ['completed', 'skipped'])
            ->count();

        $totalCount = TenderStageLog::where('tender_id', $this->tender_id)->count();

        $this->tender->update([
            'current_stage' => $nextStage?->stage ?? 'archived',
            'completion_percentage' => $totalCount > 0 ? ($completedCount / $totalCount) * 100 : 0,
        ]);
    }

    // Static Methods
    public static function initializeForTender(Tender $tender): void
    {
        foreach (self::STAGES as $stage => $config) {
            self::firstOrCreate(
                ['tender_id' => $tender->id, 'stage' => $stage],
                [
                    'status' => 'not_started',
                    'stage_order' => $config['order'],
                    'is_mandatory' => $config['mandatory'],
                ]
            );
        }

        $tender->update(['current_stage' => 'discovery']);
    }

    public static function getOrderedStages(): array
    {
        return collect(self::STAGES)
            ->sortBy('order')
            ->map(fn($config, $key) => [
                'key' => $key,
                'label' => $config['label'],
                'order' => $config['order'],
                'mandatory' => $config['mandatory'],
            ])
            ->values()
            ->all();
    }

    // Accessors
    public function getStageLabelAttribute(): string
    {
        return self::STAGES[$this->stage]['label'] ?? $this->stage;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getIsInProgressAttribute(): bool
    {
        return $this->status === 'in_progress';
    }

    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }
        return $this->started_at->diffInDays($this->completed_at);
    }
}
