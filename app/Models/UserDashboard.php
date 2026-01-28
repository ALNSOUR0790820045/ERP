<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDashboard extends Model
{
    protected $fillable = [
        'user_id',
        'dashboard_id',
        'is_default',
        'custom_layout',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'custom_layout' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }
}
