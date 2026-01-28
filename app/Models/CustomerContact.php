<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerContact extends Model
{
    protected $fillable = [
        'customer_id',
        'name',
        'position',
        'department',
        'phone',
        'mobile',
        'email',
        'is_primary',
        'is_decision_maker',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_decision_maker' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    protected static function booted(): void
    {
        static::saving(function ($contact) {
            // إذا تم تحديد هذا كجهة اتصال رئيسية، قم بإلغاء الآخرين
            if ($contact->is_primary) {
                static::where('customer_id', $contact->customer_id)
                    ->where('id', '!=', $contact->id)
                    ->update(['is_primary' => false]);
            }
        });
    }
}
