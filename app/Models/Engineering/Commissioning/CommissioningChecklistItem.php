<?php

namespace App\Models\Engineering\Commissioning;

use App\Models\Engineering\PunchList\PunchItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissioningChecklistItem extends Model
{
    protected $fillable = [
        'checklist_id',
        'item_number',
        'test_description',
        'acceptance_criteria',
        'expected_value',
        'actual_value',
        'result',
        'remarks',
        'punch_item_id',
    ];

    protected $casts = [
        'item_number' => 'integer',
    ];

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(CommissioningChecklist::class, 'checklist_id');
    }

    public function punchItem(): BelongsTo
    {
        return $this->belongsTo(PunchItem::class, 'punch_item_id');
    }

    public function pass(string $actualValue = null, string $remarks = null): void
    {
        $this->update([
            'result' => 'pass',
            'actual_value' => $actualValue,
            'remarks' => $remarks,
        ]);
        $this->checklist->updateResults();
    }

    public function fail(string $actualValue = null, string $remarks = null): void
    {
        $this->update([
            'result' => 'fail',
            'actual_value' => $actualValue,
            'remarks' => $remarks,
        ]);
        $this->checklist->updateResults();
    }

    public function markNotApplicable(string $remarks = null): void
    {
        $this->update([
            'result' => 'na',
            'remarks' => $remarks,
        ]);
        $this->checklist->updateResults();
    }

    public function createPunchItem(array $data): PunchItem
    {
        $punchItem = PunchItem::create($data);
        $this->update(['punch_item_id' => $punchItem->id]);
        return $punchItem;
    }
}
