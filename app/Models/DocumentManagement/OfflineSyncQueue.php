<?php

namespace App\Models\DocumentManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Offline Sync Queue Model
 * قائمة المزامنة للعمل بدون اتصال
 */
class OfflineSyncQueue extends Model
{
    protected $table = 'offline_sync_queue';

    protected $fillable = [
        'user_id',
        'device_id',
        'sync_type',
        'syncable_type',
        'syncable_id',
        'action',
        'local_data',
        'server_data',
        'status',
        'conflict_resolution',
        'retry_count',
        'queued_at',
        'synced_at',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'local_data' => 'array',
        'server_data' => 'array',
        'metadata' => 'array',
        'queued_at' => 'datetime',
        'synced_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    // Status Constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SYNCED = 'synced';
    const STATUS_CONFLICT = 'conflict';
    const STATUS_FAILED = 'failed';

    // Action Constants
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';

    // Sync Types
    const TYPE_DOCUMENT = 'document';
    const TYPE_RFI = 'rfi';
    const TYPE_TRANSMITTAL = 'transmittal';
    const TYPE_INSPECTION = 'inspection';

    // Conflict Resolution
    const RESOLVE_SERVER_WINS = 'server_wins';
    const RESOLVE_CLIENT_WINS = 'client_wins';
    const RESOLVE_MERGE = 'merge';
    const RESOLVE_MANUAL = 'manual';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function syncable(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForDevice($query, string $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeHasConflict($query)
    {
        return $query->where('status', self::STATUS_CONFLICT);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    // Helper Methods
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function hasConflict(): bool
    {
        return $this->status === self::STATUS_CONFLICT;
    }

    public function isSynced(): bool
    {
        return $this->status === self::STATUS_SYNCED;
    }

    public function canRetry(): bool
    {
        return $this->retry_count < 3 && !$this->isSynced();
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    public function markAsSynced(): void
    {
        $this->update([
            'status' => self::STATUS_SYNCED,
            'synced_at' => now(),
        ]);
    }

    public function markAsConflict(array $serverData): void
    {
        $this->update([
            'status' => self::STATUS_CONFLICT,
            'server_data' => $serverData,
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $error,
        ]);
    }

    public function retry(): void
    {
        if ($this->canRetry()) {
            $this->increment('retry_count');
            $this->update(['status' => self::STATUS_PENDING]);
        }
    }

    public function resolveConflict(string $resolution): void
    {
        $this->update([
            'conflict_resolution' => $resolution,
            'status' => self::STATUS_PENDING,
        ]);
    }

    public static function queueAction(
        User $user,
        string $deviceId,
        string $syncType,
        Model $model,
        string $action,
        array $data
    ): self {
        return static::create([
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'sync_type' => $syncType,
            'syncable_type' => get_class($model),
            'syncable_id' => $model->id,
            'action' => $action,
            'local_data' => $data,
            'status' => self::STATUS_PENDING,
            'queued_at' => now(),
        ]);
    }

    public static function processQueue(string $deviceId): array
    {
        $results = ['synced' => 0, 'conflicts' => 0, 'failed' => 0];
        
        $items = static::pending()
            ->forDevice($deviceId)
            ->orderBy('queued_at')
            ->get();

        foreach ($items as $item) {
            try {
                $item->process();
                $results['synced']++;
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'conflict')) {
                    $results['conflicts']++;
                } else {
                    $results['failed']++;
                }
            }
        }

        return $results;
    }

    public function process(): void
    {
        $this->markAsProcessing();

        try {
            switch ($this->action) {
                case self::ACTION_CREATE:
                    $this->processCreate();
                    break;
                case self::ACTION_UPDATE:
                    $this->processUpdate();
                    break;
                case self::ACTION_DELETE:
                    $this->processDelete();
                    break;
            }
            $this->markAsSynced();
        } catch (\Exception $e) {
            $this->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    protected function processCreate(): void
    {
        $class = $this->syncable_type;
        $class::create($this->local_data);
    }

    protected function processUpdate(): void
    {
        $model = $this->syncable;
        if (!$model) {
            throw new \Exception('Model not found');
        }

        // Check for conflicts
        if ($model->updated_at > $this->queued_at) {
            $this->markAsConflict($model->toArray());
            throw new \Exception('Conflict detected');
        }

        $model->update($this->local_data);
    }

    protected function processDelete(): void
    {
        $model = $this->syncable;
        if ($model) {
            $model->delete();
        }
    }
}
