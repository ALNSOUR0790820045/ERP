<?php

namespace App\Models\SupplierManagement;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupplierMessage extends Model
{
    protected $fillable = [
        'supplier_id', 'subject', 'message', 'direction', 'sent_by', 'portal_user_id',
        'parent_id', 'regarding_type', 'regarding_id', 'read_at', 'attachments', 'metadata',
    ];

    protected $casts = [
        'read_at' => 'datetime', 'attachments' => 'array', 'metadata' => 'array',
    ];

    const DIRECTION_INBOUND = 'inbound';
    const DIRECTION_OUTBOUND = 'outbound';

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function sentBy(): BelongsTo { return $this->belongsTo(User::class, 'sent_by'); }
    public function portalUser(): BelongsTo { return $this->belongsTo(SupplierPortalUser::class, 'portal_user_id'); }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function replies(): HasMany { return $this->hasMany(self::class, 'parent_id'); }
    public function regarding(): MorphTo { return $this->morphTo(); }

    public function scopeUnread($q) { return $q->whereNull('read_at'); }
    public function scopeInbound($q) { return $q->where('direction', self::DIRECTION_INBOUND); }
    public function scopeOutbound($q) { return $q->where('direction', self::DIRECTION_OUTBOUND); }

    public function isRead(): bool { return !is_null($this->read_at); }
    public function markAsRead(): void { if (!$this->read_at) $this->update(['read_at' => now()]); }

    public function reply(string $message, User $user = null, SupplierPortalUser $portalUser = null): self
    {
        return static::create([
            'supplier_id' => $this->supplier_id, 'subject' => 'Re: ' . $this->subject,
            'message' => $message, 'parent_id' => $this->id,
            'direction' => $user ? self::DIRECTION_OUTBOUND : self::DIRECTION_INBOUND,
            'sent_by' => $user?->id, 'portal_user_id' => $portalUser?->id,
            'regarding_type' => $this->regarding_type, 'regarding_id' => $this->regarding_id,
        ]);
    }
}
