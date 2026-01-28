<?php

namespace App\Models\SupplierManagement;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlanketPurchaseAgreement extends Model
{
    protected $fillable = [
        'supplier_id', 'agreement_code', 'agreement_number', 'title', 'description',
        'agreement_type', 'status', 'currency', 'start_date', 'end_date', 'min_amount',
        'max_amount', 'committed_amount', 'released_amount', 'remaining_amount',
        'min_quantity', 'max_quantity', 'released_quantity', 'payment_terms', 'delivery_terms',
        'price_adjustment_clause', 'escalation_percentage', 'terms_and_conditions',
        'auto_renew', 'renewal_terms', 'notice_period_days', 'created_by', 'approved_by',
        'approved_at', 'terminated_by', 'terminated_at', 'termination_reason', 'metadata',
    ];

    protected $casts = [
        'start_date' => 'date', 'end_date' => 'date', 'approved_at' => 'datetime', 'terminated_at' => 'datetime',
        'min_amount' => 'decimal:2', 'max_amount' => 'decimal:2', 'committed_amount' => 'decimal:2',
        'released_amount' => 'decimal:2', 'remaining_amount' => 'decimal:2',
        'min_quantity' => 'decimal:3', 'max_quantity' => 'decimal:3', 'released_quantity' => 'decimal:3',
        'escalation_percentage' => 'decimal:2', 'auto_renew' => 'boolean', 'metadata' => 'array',
    ];

    const TYPE_STANDARD = 'standard';
    const TYPE_PLANNED = 'planned';
    const TYPE_CONTRACT = 'contract';
    const TYPE_CATALOG = 'catalog';

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_TERMINATED = 'terminated';

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany { return $this->hasMany(BlanketAgreementItem::class, 'blanket_agreement_id'); }
    public function releases(): HasMany { return $this->hasMany(BlanketAgreementRelease::class, 'blanket_agreement_id'); }

    public function scopeActive($q) { return $q->where('status', self::STATUS_ACTIVE)->where('end_date', '>=', now()); }
    public function scopeExpiringSoon($q, int $days = 30) {
        return $q->where('status', self::STATUS_ACTIVE)->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    public function approve(User $approver): void {
        $this->update(['status' => self::STATUS_APPROVED, 'approved_by' => $approver->id, 'approved_at' => now()]);
    }

    public function activate(): void {
        if ($this->status === self::STATUS_APPROVED && $this->start_date <= now()) {
            $this->update(['status' => self::STATUS_ACTIVE]);
        }
    }

    public function release(float $amount, float $quantity = null): BlanketAgreementRelease {
        return $this->releases()->create([
            'released_amount' => $amount, 'released_quantity' => $quantity,
            'release_date' => now(), 'status' => 'pending',
        ]);
    }

    public function updateReleased(): void {
        $this->update([
            'released_amount' => $this->releases()->sum('released_amount'),
            'released_quantity' => $this->releases()->sum('released_quantity'),
            'remaining_amount' => $this->max_amount - $this->releases()->sum('released_amount'),
        ]);
    }

    public function isWithinLimits(float $amount): bool {
        return ($this->released_amount + $amount) <= $this->max_amount;
    }

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->agreement_code)) {
                $model->agreement_code = 'BPA-' . date('Ymd') . '-' . str_pad(static::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
