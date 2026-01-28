<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Correspondence extends Model
{
    protected $table = 'correspondences';

    protected $fillable = [
        'project_id', 'company_id', 'reference_number', 'correspondence_date',
        'correspondence_type', 'direction', 'from_party', 'to_party', 'attention',
        'cc', 'subject', 'content', 'priority', 'status',
        'response_required_date', 'response_date', 'created_by',
    ];

    protected $casts = [
        'correspondence_date' => 'date',
        'response_required_date' => 'date',
        'response_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
