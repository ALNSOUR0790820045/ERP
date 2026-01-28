<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentDistribution extends Model
{
    protected $fillable = [
        'document_id', 'revision_id', 'distribution_type', 'recipient_type',
        'recipient_id', 'recipient_name', 'recipient_email', 'distribution_date',
        'distribution_method', 'copies_count', 'acknowledged', 'acknowledged_at',
    ];

    protected $casts = [
        'distribution_date' => 'date',
        'acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(DocumentRevision::class, 'revision_id');
    }

    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }
}
