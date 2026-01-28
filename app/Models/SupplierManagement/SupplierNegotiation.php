<?php

namespace App\Models\SupplierManagement;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierNegotiation extends Model
{
    protected $fillable = [
        'supplier_id', 'negotiation_code', 'negotiation_type', 'title', 'description',
        'reference_type', 'reference_id', 'status', 'lead_negotiator_id', 'negotiation_team',
        'start_date', 'target_end_date', 'actual_end_date', 'original_value', 'target_value',
        'final_value', 'savings_amount', 'savings_percentage', 'currency', 'rounds',
        'current_round', 'negotiation_history', 'terms_negotiated', 'concessions_given',
        'concessions_received', 'outcome', 'outcome_notes', 'lessons_learned', 'metadata',
    ];

    protected $casts = [
        'start_date' => 'date', 'target_end_date' => 'date', 'actual_end_date' => 'date',
        'original_value' => 'decimal:2', 'target_value' => 'decimal:2', 'final_value' => 'decimal:2',
        'savings_amount' => 'decimal:2', 'savings_percentage' => 'decimal:2',
        'negotiation_team' => 'array', 'negotiation_history' => 'array',
        'terms_negotiated' => 'array', 'concessions_given' => 'array',
        'concessions_received' => 'array', 'metadata' => 'array',
    ];

    const TYPE_PRICE = 'price';
    const TYPE_CONTRACT = 'contract';
    const TYPE_TERMS = 'terms';
    const TYPE_RENEWAL = 'renewal';
    const TYPE_DISPUTE = 'dispute';

    const STATUS_PLANNING = 'planning';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_PENDING = 'pending_response';
    const STATUS_CONCLUDED = 'concluded';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    const OUTCOME_SUCCESS = 'success';
    const OUTCOME_PARTIAL = 'partial';
    const OUTCOME_FAILED = 'failed';
    const OUTCOME_WALKAWAY = 'walkaway';

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function leadNegotiator(): BelongsTo { return $this->belongsTo(User::class, 'lead_negotiator_id'); }

    public function scopeActive($q) { return $q->whereIn('status', [self::STATUS_PLANNING, self::STATUS_IN_PROGRESS, self::STATUS_PENDING]); }
    public function scopeSuccessful($q) { return $q->where('outcome', self::OUTCOME_SUCCESS); }

    public function addRound(array $roundData): void {
        $history = $this->negotiation_history ?? [];
        $roundData['round_number'] = count($history) + 1;
        $roundData['date'] = now()->toDateString();
        $history[] = $roundData;
        $this->update(['negotiation_history' => $history, 'current_round' => count($history)]);
    }

    public function calculateSavings(): void {
        if ($this->original_value && $this->final_value) {
            $this->savings_amount = $this->original_value - $this->final_value;
            $this->savings_percentage = $this->original_value > 0 
                ? (($this->original_value - $this->final_value) / $this->original_value) * 100 : 0;
            $this->save();
        }
    }

    public function conclude(string $outcome, float $finalValue = null, string $notes = null): void {
        $this->update([
            'status' => self::STATUS_CONCLUDED, 'outcome' => $outcome,
            'final_value' => $finalValue ?? $this->final_value,
            'actual_end_date' => now(), 'outcome_notes' => $notes,
        ]);
        $this->calculateSavings();
    }

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->negotiation_code)) {
                $model->negotiation_code = 'NEG-' . date('Ymd') . '-' . str_pad(static::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
