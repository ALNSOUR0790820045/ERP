<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractClaimDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_id',
        'document_type',
        'title',
        'description',
        'file_path',
        'document_date',
    ];

    protected $casts = [
        'document_date' => 'date',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(ContractClaim::class, 'claim_id');
    }
}
