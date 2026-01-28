<?php

namespace App\Models\DocumentManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Submittal Revision Model
 * مراجعات التسليمات
 */
class SubmittalRevision extends Model
{
    protected $fillable = [
        'submittal_id',
        'revision_number',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'description',
        'status',
        'submitted_by',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_status',
        'review_comments',
        'metadata',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'file_size' => 'integer',
        'metadata' => 'array',
    ];

    // Status Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_REVIEWED = 'reviewed';

    // Review Status Constants
    const REVIEW_APPROVED = 'approved';
    const REVIEW_APPROVED_AS_NOTED = 'approved_as_noted';
    const REVIEW_REVISE_RESUBMIT = 'revise_resubmit';
    const REVIEW_REJECTED = 'rejected';

    public function submittal(): BelongsTo
    {
        return $this->belongsTo(Submittal::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reviewCycles(): HasMany
    {
        return $this->hasMany(SubmittalReviewCycle::class);
    }

    // Scopes
    public function scopeForSubmittal($query, int $submittalId)
    {
        return $query->where('submittal_id', $submittalId);
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('revision_number');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
        ]);
    }

    // Helper Methods
    public function isApproved(): bool
    {
        return in_array($this->review_status, [
            self::REVIEW_APPROVED,
            self::REVIEW_APPROVED_AS_NOTED,
        ]);
    }

    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
        ]);
    }

    public function getFileSizeFormatted(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    public function submit(): void
    {
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        // Update submittal current revision
        $this->submittal->update([
            'current_revision' => $this->revision_number,
            'status' => Submittal::STATUS_SUBMITTED,
        ]);
    }

    public function review(User $reviewer, string $status, string $comments = null): void
    {
        $this->update([
            'status' => self::STATUS_REVIEWED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_status' => $status,
            'review_comments' => $comments,
        ]);

        // Update parent submittal status
        $submittalStatus = match ($status) {
            self::REVIEW_APPROVED => Submittal::STATUS_APPROVED,
            self::REVIEW_APPROVED_AS_NOTED => Submittal::STATUS_APPROVED_AS_NOTED,
            self::REVIEW_REVISE_RESUBMIT => Submittal::STATUS_REVISE_RESUBMIT,
            self::REVIEW_REJECTED => Submittal::STATUS_REJECTED,
            default => $this->submittal->status,
        };

        $this->submittal->update(['status' => $submittalStatus]);
    }

    public static function createForSubmittal(Submittal $submittal, array $data): self
    {
        $latestRevision = $submittal->getLatestRevision();
        $newNumber = $latestRevision ? $latestRevision->revision_number + 1 : 1;

        return static::create(array_merge($data, [
            'submittal_id' => $submittal->id,
            'revision_number' => $newNumber,
            'status' => self::STATUS_DRAFT,
        ]));
    }
}
