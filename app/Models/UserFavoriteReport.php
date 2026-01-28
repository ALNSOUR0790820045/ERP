<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFavoriteReport extends Model
{
    protected $fillable = [
        'user_id',
        'report_definition_id',
        'default_parameters',
    ];

    protected $casts = [
        'default_parameters' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reportDefinition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class);
    }
}
