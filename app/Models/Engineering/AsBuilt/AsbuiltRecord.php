<?php

namespace App\Models\Engineering\AsBuilt;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AsbuiltRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'record_number',
        'title',
        'description',
        'document_type',
        'discipline',
        'area',
        'system',
        'original_document_number',
        'revision',
        'document_date',
        'file_path',
        'status',
        'submitted_by',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_comments',
    ];

    protected $casts = [
        'document_date' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(
            AsbuiltPackage::class,
            'asbuilt_package_documents',
            'record_id',
            'package_id'
        )->withPivot('sequence')->withTimestamps();
    }

    public function submit(int $userId): void
    {
        $this->update([
            'status' => 'submitted',
            'submitted_by' => $userId,
            'submitted_at' => now(),
        ]);
    }

    public function review(int $userId, string $result, string $comments = null): void
    {
        $this->update([
            'status' => $result, // 'approved' or 'rejected'
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'review_comments' => $comments,
        ]);
    }

    public static function generateNumber(int $projectId): string
    {
        $count = self::where('project_id', $projectId)->count() + 1;
        return sprintf('AB-%d-%04d', $projectId, $count);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'submitted']);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeByDiscipline($query, string $discipline)
    {
        return $query->where('discipline', $discipline);
    }
}
