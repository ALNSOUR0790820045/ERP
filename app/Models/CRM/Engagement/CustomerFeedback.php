<?php

namespace App\Models\CRM\Engagement;

use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerContact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerFeedback extends Model
{
    protected $table = 'customer_feedback';

    protected $fillable = [
        'feedback_number',
        'customer_id',
        'contact_id',
        'feedback_type',
        'channel',
        'subject',
        'content',
        'sentiment',
        'priority',
        'status',
        'response',
        'assigned_to',
        'responded_by',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    // Boot
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($feedback) {
            if (empty($feedback->feedback_number)) {
                $feedback->feedback_number = 'FB-' . date('Y') . '-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    // العلاقات
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CustomerContact::class, 'contact_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    // Scopes
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['new', 'in_review']);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('feedback_type', $type);
    }

    public function scopeBySentiment($query, string $sentiment)
    {
        return $query->where('sentiment', $sentiment);
    }

    public function scopePositive($query)
    {
        return $query->where('sentiment', 'positive');
    }

    public function scopeNegative($query)
    {
        return $query->where('sentiment', 'negative');
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    // Methods
    public function respond(string $response): void
    {
        $this->update([
            'response' => $response,
            'status' => 'actioned',
            'responded_by' => auth()->id(),
            'responded_at' => now(),
        ]);
    }

    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    public function assignTo(int $userId): void
    {
        $this->update([
            'assigned_to' => $userId,
            'status' => 'in_review',
        ]);
    }

    public function isPositive(): bool
    {
        return $this->sentiment === 'positive';
    }

    public function isNegative(): bool
    {
        return $this->sentiment === 'negative';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['new', 'in_review']);
    }

    // إحصائيات
    public static function getSentimentDistribution(int $days = 30): array
    {
        return static::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('sentiment, COUNT(*) as count')
            ->groupBy('sentiment')
            ->pluck('count', 'sentiment')
            ->toArray();
    }
}
