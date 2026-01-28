<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractCorrespondence extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'project_id', 'reference_number', 'correspondence_type',
        'direction', 'subject', 'content', 'sender', 'recipient',
        'cc_recipients', 'date_sent', 'date_received', 'response_required',
        'response_due_date', 'response_received', 'related_correspondence_id',
        'attachments', 'status', 'priority', 'created_by', 'notes',
    ];

    protected $casts = [
        'date_sent' => 'date',
        'date_received' => 'date',
        'response_due_date' => 'date',
        'response_required' => 'boolean',
        'response_received' => 'boolean',
        'cc_recipients' => 'array',
        'attachments' => 'array',
    ];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function relatedCorrespondence(): BelongsTo { return $this->belongsTo(ContractCorrespondence::class, 'related_correspondence_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeIncoming($query) { return $query->where('direction', 'incoming'); }
    public function scopeOutgoing($query) { return $query->where('direction', 'outgoing'); }
    public function scopeAwaitingResponse($query) { 
        return $query->where('response_required', true)->where('response_received', false); 
    }
}
