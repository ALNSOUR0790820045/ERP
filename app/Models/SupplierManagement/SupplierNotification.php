<?php

namespace App\Models\SupplierManagement;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupplierNotification extends Model
{
    protected $fillable = [
        'supplier_id', 'portal_user_id', 'notification_type', 'title', 'message',
        'priority', 'action_url', 'notifiable_type', 'notifiable_id',
        'read_at', 'sent_at', 'sent_via', 'metadata',
    ];

    protected $casts = [
        'read_at' => 'datetime', 'sent_at' => 'datetime', 'metadata' => 'array',
    ];

    const TYPE_RFQ = 'rfq';
    const TYPE_PO = 'purchase_order';
    const TYPE_PAYMENT = 'payment';
    const TYPE_DOCUMENT = 'document';
    const TYPE_EVALUATION = 'evaluation';
    const TYPE_SYSTEM = 'system';

    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function portalUser(): BelongsTo { return $this->belongsTo(SupplierPortalUser::class, 'portal_user_id'); }
    public function notifiable(): MorphTo { return $this->morphTo(); }

    public function scopeUnread($q) { return $q->whereNull('read_at'); }
    public function scopeUrgent($q) { return $q->where('priority', self::PRIORITY_URGENT); }

    public function isRead(): bool { return !is_null($this->read_at); }
    public function markAsRead(): void { $this->update(['read_at' => now()]); }

    public static function notify(Supplier $supplier, string $type, string $title, string $message, Model $related = null): self
    {
        return static::create([
            'supplier_id' => $supplier->id, 'notification_type' => $type,
            'title' => $title, 'message' => $message,
            'notifiable_type' => $related ? get_class($related) : null,
            'notifiable_id' => $related?->id, 'sent_at' => now(),
        ]);
    }
}
