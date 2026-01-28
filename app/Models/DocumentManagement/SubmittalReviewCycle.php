<?php

namespace App\Models\DocumentManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Submittal Review Cycle Model
 * دورات مراجعة التسليمات
 */
class SubmittalReviewCycle extends Model
{
    protected $fillable = [
        'submittal_id',
        'revision_id',
        'cycle_number',
        'reviewer_id',
        'reviewer_role',
        'review_order',
        'status',
        'assigned_at',
        'due_date',
        'started_at',
        'completed_at',
        'review_result',
        'comments',
        'markup_file',
        'is_current',
        'metadata',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'due_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_current' => 'boolean',
        'review_order' => 'integer',
        'cycle_number' => 'integer',
        'metadata' => 'array',
    ];

    // Status Constants
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_SKIPPED = 'skipped';

    // Review Result Constants
    const RESULT_APPROVED = 'approved';
    const RESULT_APPROVED_AS_NOTED = 'approved_as_noted';
    const RESULT_REVISE_RESUBMIT = 'revise_resubmit';
    const RESULT_REJECTED = 'rejected';
    const RESULT_NO_EXCEPTIONS = 'no_exceptions';

    // Reviewer Roles
    const ROLE_ARCHITECT = 'architect';
    const ROLE_ENGINEER = 'engineer';
    const ROLE_CONSULTANT = 'consultant';
    const ROLE_OWNER = 'owner';
    const ROLE_CONTRACTOR = 'contractor';

    public function submittal(): BelongsTo
    {
        return $this->belongsTo(Submittal::class);
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(SubmittalRevision::class, 'revision_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    // Scopes
    public function scopeForSubmittal($query, int $submittalId)
    {
        return $query->where('submittal_id', $submittalId);
    }

    public function scopeForRevision($query, int $revisionId)
    {
        return $query->where('revision_id', $revisionId);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeOverdue($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    // Helper Methods
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isOverdue(): bool
    {
        return !$this->isCompleted() && $this->due_date && $this->due_date->isPast();
    }

    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) return 0;
        return $this->due_date->diffInDays(now());
    }

    public function getReviewDuration(): ?int
    {
        if (!$this->started_at || !$this->completed_at) return null;
        return $this->started_at->diffInDays($this->completed_at);
    }

    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    public function complete(string $result, string $comments = null, string $markupFile = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'review_result' => $result,
            'comments' => $comments,
            'markup_file' => $markupFile,
        ]);

        // Check if all review cycles are completed
        $this->checkAndProceed();
    }

    public function skip(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_SKIPPED,
            'completed_at' => now(),
            'comments' => $reason,
        ]);
    }

    protected function checkAndProceed(): void
    {
        // Get next reviewer in order
        $nextCycle = static::forSubmittal($this->submittal_id)
            ->forRevision($this->revision_id)
            ->where('review_order', '>', $this->review_order)
            ->pending()
            ->orderBy('review_order')
            ->first();

        if ($nextCycle) {
            $nextCycle->start();
        } else {
            // All reviews completed - update revision and submittal
            $this->finalizeReview();
        }
    }

    protected function finalizeReview(): void
    {
        $cycles = static::forRevision($this->revision_id)->completed()->get();
        
        // Determine final result based on all reviews
        $results = $cycles->pluck('review_result')->unique();
        
        $finalResult = match (true) {
            $results->contains(self::RESULT_REJECTED) => SubmittalRevision::REVIEW_REJECTED,
            $results->contains(self::RESULT_REVISE_RESUBMIT) => SubmittalRevision::REVIEW_REVISE_RESUBMIT,
            $results->contains(self::RESULT_APPROVED_AS_NOTED) => SubmittalRevision::REVIEW_APPROVED_AS_NOTED,
            default => SubmittalRevision::REVIEW_APPROVED,
        };

        // Update the revision with final result
        $revision = $this->revision;
        if ($revision) {
            $revision->update([
                'review_status' => $finalResult,
                'status' => SubmittalRevision::STATUS_REVIEWED,
                'reviewed_at' => now(),
            ]);
        }
    }

    public static function createCycle(
        Submittal $submittal,
        SubmittalRevision $revision,
        User $reviewer,
        string $role,
        int $order = 1
    ): self {
        $cycleNumber = static::forSubmittal($submittal->id)->max('cycle_number') + 1;

        return static::create([
            'submittal_id' => $submittal->id,
            'revision_id' => $revision->id,
            'cycle_number' => $cycleNumber,
            'reviewer_id' => $reviewer->id,
            'reviewer_role' => $role,
            'review_order' => $order,
            'status' => self::STATUS_PENDING,
            'assigned_at' => now(),
            'is_current' => true,
        ]);
    }
}
