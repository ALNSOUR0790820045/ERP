<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transmittal extends Model
{
    protected $fillable = [
        'project_id', 'transmittal_number', 'transmittal_date', 'transmittal_type',
        'from_party', 'to_party', 'attention', 'subject', 'purpose', 'remarks',
        'delivery_method', 'status', 'created_by',
    ];

    protected $casts = [
        'transmittal_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransmittalItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
