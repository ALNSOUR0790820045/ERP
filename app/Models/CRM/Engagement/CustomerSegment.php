<?php

namespace App\Models\CRM\Engagement;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CustomerSegment extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'description',
        'segment_type',
        'criteria',
        'customer_count',
        'total_revenue',
        'last_refreshed_at',
        'is_active',
    ];

    protected $casts = [
        'criteria' => 'array',
        'customer_count' => 'integer',
        'total_revenue' => 'decimal:2',
        'last_refreshed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function members(): HasMany
    {
        return $this->hasMany(CustomerSegmentMember::class, 'segment_id');
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_segment_members', 'segment_id', 'customer_id')
            ->withPivot('added_at', 'removed_at')
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeStatic($query)
    {
        return $query->where('segment_type', 'static');
    }

    public function scopeDynamic($query)
    {
        return $query->where('segment_type', 'dynamic');
    }

    // Accessors
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    // Methods
    public function addCustomer(int $customerId): CustomerSegmentMember
    {
        // التحقق من عدم الوجود مسبقاً
        $existing = $this->members()
            ->where('customer_id', $customerId)
            ->whereNull('removed_at')
            ->first();
            
        if ($existing) return $existing;
        
        return $this->members()->create([
            'customer_id' => $customerId,
            'added_at' => now(),
        ]);
    }

    public function removeCustomer(int $customerId): void
    {
        $this->members()
            ->where('customer_id', $customerId)
            ->whereNull('removed_at')
            ->update(['removed_at' => now()]);
    }

    public function refresh(): void
    {
        if ($this->segment_type !== 'dynamic') return;
        
        // تطبيق المعايير للحصول على العملاء
        $customerIds = $this->applyDynamicCriteria();
        
        // إزالة العملاء القدامى
        $this->members()->whereNull('removed_at')->update(['removed_at' => now()]);
        
        // إضافة العملاء الجدد
        foreach ($customerIds as $customerId) {
            $this->addCustomer($customerId);
        }
        
        // تحديث الإحصائيات
        $this->updateStats();
    }

    private function applyDynamicCriteria(): array
    {
        $query = Customer::query();
        
        foreach ($this->criteria ?? [] as $criterion) {
            $field = $criterion['field'];
            $operator = $criterion['operator'];
            $value = $criterion['value'];
            
            match($operator) {
                'equals' => $query->where($field, $value),
                'not_equals' => $query->where($field, '!=', $value),
                'greater_than' => $query->where($field, '>', $value),
                'less_than' => $query->where($field, '<', $value),
                'contains' => $query->where($field, 'like', "%{$value}%"),
                'in' => $query->whereIn($field, (array) $value),
                default => null,
            };
        }
        
        return $query->pluck('id')->toArray();
    }

    public function updateStats(): void
    {
        $activeMembers = $this->members()->whereNull('removed_at')->get();
        
        $this->customer_count = $activeMembers->count();
        // $this->total_revenue = يمكن حسابها من الفواتير
        $this->last_refreshed_at = now();
        $this->save();
    }

    public function getActiveCustomerIds(): array
    {
        return $this->members()
            ->whereNull('removed_at')
            ->pluck('customer_id')
            ->toArray();
    }
}
