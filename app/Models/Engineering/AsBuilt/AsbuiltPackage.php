<?php

namespace App\Models\Engineering\AsBuilt;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AsbuiltPackage extends Model
{
    protected $fillable = [
        'project_id',
        'package_number',
        'name',
        'description',
        'discipline',
        'due_date',
        'submission_date',
        'total_documents',
        'approved_documents',
        'completion_percentage',
        'status',
        'responsible_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'submission_date' => 'date',
        'total_documents' => 'integer',
        'approved_documents' => 'integer',
        'completion_percentage' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function records(): BelongsToMany
    {
        return $this->belongsToMany(
            AsbuiltRecord::class,
            'asbuilt_package_documents',
            'package_id',
            'record_id'
        )->withPivot('sequence')->withTimestamps()->orderBy('sequence');
    }

    public function updateCompletion(): void
    {
        $this->total_documents = $this->records()->count();
        $this->approved_documents = $this->records()->where('status', 'approved')->count();
        $this->completion_percentage = $this->total_documents > 0 
            ? round(($this->approved_documents / $this->total_documents) * 100, 2) 
            : 0;
        $this->save();
    }

    public function addRecord(int $recordId, int $sequence = 0): void
    {
        $this->records()->attach($recordId, ['sequence' => $sequence]);
        $this->updateCompletion();
    }

    public function removeRecord(int $recordId): void
    {
        $this->records()->detach($recordId);
        $this->updateCompletion();
    }

    public function submit(): void
    {
        $this->update([
            'status' => 'submitted',
            'submission_date' => now(),
        ]);
    }

    public static function generateNumber(int $projectId): string
    {
        $count = self::where('project_id', $projectId)->count() + 1;
        return sprintf('AB-PKG-%d-%03d', $projectId, $count);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['not_started', 'in_progress']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', ['approved', 'submitted']);
    }
}
