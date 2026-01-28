<?php

namespace App\Models;

use App\Enums\BondType;
use App\Enums\OwnerType;
use App\Enums\SubmissionMethod;
use App\Enums\TenderMethod;
use App\Enums\TenderResult;
use App\Enums\TenderStatus;
use App\Enums\TenderType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tender extends Model
{
    use SoftDeletes;

    protected $fillable = [
        // التعريف
        'tender_number',
        'reference_number',
        'name_ar',
        'name_en',
        'description',
        
        // التصنيف
        'tender_type',
        'tender_method',
        'project_type_id',
        'specialization_id',
        
        // الجهة المالكة
        'owner_type',
        'owner_id',
        'owner_name',
        'owner_contact_person',
        'owner_phone',
        'owner_email',
        'owner_address',
        
        // الاستشاري
        'consultant_id',
        'consultant_name',
        
        // الموقع
        'country',
        'city',
        'site_address',
        'latitude',
        'longitude',
        
        // التواريخ
        'publication_date',
        'documents_sale_start',
        'documents_sale_end',
        'site_visit_date',
        'questions_deadline',
        'submission_deadline',
        'opening_date',
        'validity_period',
        'expected_award_date',
        
        // القيم
        'estimated_value',
        'currency_id',
        'documents_price',
        
        // الكفالات
        'bid_bond_type',
        'bid_bond_percentage',
        'bid_bond_amount',
        'performance_bond_percentage',
        'advance_payment_percentage',
        'retention_percentage',
        
        // المتطلبات
        'required_classification',
        'minimum_experience_years',
        'minimum_similar_projects',
        'minimum_project_value',
        'financial_requirements',
        'technical_requirements',
        'other_requirements',
        
        // التسعير
        'total_direct_cost',
        'total_overhead',
        'total_cost',
        'markup_percentage',
        'markup_amount',
        'submitted_price',
        
        // الحالة
        'status',
        'decision',
        'decision_date',
        'decision_by',
        'decision_notes',
        
        // التقديم
        'submission_date',
        'submission_method',
        'submitted_by',
        'receipt_number',
        
        // النتيجة
        'result',
        'award_date',
        'winner_name',
        'winning_price',
        'our_rank',
        'loss_reason',
        'lessons_learned',
        
        // العقد
        'contract_id',
        
        // التدقيق
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tender_type' => TenderType::class,
        'tender_method' => TenderMethod::class,
        'owner_type' => OwnerType::class,
        'bid_bond_type' => BondType::class,
        'submission_method' => SubmissionMethod::class,
        'status' => TenderStatus::class,
        'result' => TenderResult::class,
        
        'publication_date' => 'date',
        'documents_sale_start' => 'date',
        'documents_sale_end' => 'date',
        'site_visit_date' => 'datetime',
        'questions_deadline' => 'datetime',
        'submission_deadline' => 'datetime',
        'opening_date' => 'datetime',
        'decision_date' => 'date',
        'submission_date' => 'datetime',
        'award_date' => 'date',
        'expected_award_date' => 'date',
        
        'estimated_value' => 'decimal:3',
        'documents_price' => 'decimal:2',
        'bid_bond_percentage' => 'decimal:2',
        'bid_bond_amount' => 'decimal:3',
        'performance_bond_percentage' => 'decimal:2',
        'advance_payment_percentage' => 'decimal:2',
        'retention_percentage' => 'decimal:2',
        'minimum_project_value' => 'decimal:3',
        'total_direct_cost' => 'decimal:3',
        'total_overhead' => 'decimal:3',
        'total_cost' => 'decimal:3',
        'markup_percentage' => 'decimal:2',
        'markup_amount' => 'decimal:3',
        'submitted_price' => 'decimal:3',
        'winning_price' => 'decimal:3',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    // العلاقات
    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class);
    }

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function decisionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TenderDocument::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(TenderPurchase::class);
    }

    public function boqItems(): HasMany
    {
        return $this->hasMany(BoqItem::class);
    }

    public function overheads(): HasMany
    {
        return $this->hasMany(TenderOverhead::class);
    }

    public function openingResults(): HasMany
    {
        return $this->hasMany(TenderOpeningResult::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TenderActivity::class)->latest();
    }

    public function decision(): HasOne
    {
        return $this->hasOne(TenderDecision::class);
    }

    // Accessors
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'en' && $this->name_en 
            ? $this->name_en 
            : $this->name_ar;
    }

    public function getDaysUntilSubmissionAttribute(): ?int
    {
        if (!$this->submission_deadline) {
            return null;
        }
        return now()->diffInDays($this->submission_deadline, false);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->days_until_submission !== null && $this->days_until_submission < 0;
    }

    public function getIsUrgentAttribute(): bool
    {
        return $this->days_until_submission !== null 
            && $this->days_until_submission >= 0 
            && $this->days_until_submission <= 7;
    }

    public function getPriceDifferenceAttribute(): ?float
    {
        if (!$this->submitted_price || !$this->winning_price) {
            return null;
        }
        return $this->submitted_price - $this->winning_price;
    }

    public function getPriceDifferencePercentageAttribute(): ?float
    {
        if (!$this->submitted_price || !$this->winning_price || $this->winning_price == 0) {
            return null;
        }
        return (($this->submitted_price - $this->winning_price) / $this->winning_price) * 100;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            TenderStatus::WON,
            TenderStatus::LOST,
            TenderStatus::CANCELLED,
            TenderStatus::NO_GO,
        ]);
    }

    public function scopeByStatus($query, TenderStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeUpcoming($query, int $days = 7)
    {
        return $query->where('submission_deadline', '>=', now())
            ->where('submission_deadline', '<=', now()->addDays($days));
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('created_at', now()->year);
    }

    // Methods
    public static function generateNumber(): string
    {
        $year = now()->year;
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('TND-%d-%04d', $year, $count);
    }

    public function calculateTotalCost(): void
    {
        $directCost = $this->boqItems()->sum('total_amount') ?? 0;
        $overhead = $this->overheads()->sum('amount') ?? 0;
        
        $this->total_direct_cost = $directCost;
        $this->total_overhead = $overhead;
        $this->total_cost = $directCost + $overhead;
        
        if ($this->markup_percentage) {
            $this->markup_amount = $this->total_cost * ($this->markup_percentage / 100);
            $this->submitted_price = $this->total_cost + $this->markup_amount;
        }
        
        $this->save();
    }

    public function logActivity(string $type, string $title, ?string $description = null, ?string $oldValue = null, ?string $newValue = null): void
    {
        $this->activities()->create([
            'activity_type' => $type,
            'title' => $title,
            'description' => $description,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'user_id' => auth()->id(),
        ]);
    }

    protected static function booted(): void
    {
        static::creating(function (Tender $tender) {
            if (!$tender->tender_number) {
                $tender->tender_number = static::generateNumber();
            }
            $tender->created_by = auth()->id();
        });

        static::updating(function (Tender $tender) {
            $tender->updated_by = auth()->id();
            
            // Log status changes
            if ($tender->isDirty('status')) {
                $tender->logActivity(
                    'status_change',
                    'تغيير الحالة',
                    null,
                    $tender->getOriginal('status')?->value,
                    $tender->status->value
                );
            }
        });
    }
}
