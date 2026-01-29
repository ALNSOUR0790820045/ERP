<?php

namespace App\Models\Engineering\PunchList;

use App\Models\User;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PunchItem extends Model
{
    protected $fillable = [
        'punch_list_id',
        'item_number',
        'location',
        'discipline',
        'description',
        'priority',
        'category',
        'assigned_to',
        'responsible_contractor_id',
        'due_date',
        'photos',
        'contractor_response',
        'completion_date',
        'completion_notes',
        'completion_photos',
        'status',
        'verified_by',
        'verified_at',
        'rejection_reason',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completion_date' => 'date',
        'photos' => 'array',
        'completion_photos' => 'array',
        'verified_at' => 'datetime',
    ];

    public function punchList(): BelongsTo
    {
        return $this->belongsTo(PunchList::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function responsibleContractor(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'responsible_contractor_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function complete(string $notes = null, array $photos = []): void
    {
        $this->update([
            'status' => 'completed',
            'completion_date' => now(),
            'completion_notes' => $notes,
            'completion_photos' => $photos,
        ]);
        $this->punchList->updateCompletion();
    }

    public function verify(int $userId): void
    {
        $this->update([
            'status' => 'verified',
            'verified_by' => $userId,
            'verified_at' => now(),
        ]);
        $this->punchList->updateCompletion();
    }

    public function reject(int $userId, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'verified_by' => $userId,
            'verified_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }

    public function scopeCritical($query)
    {
        return $query->where('priority', 'critical');
    }
}
