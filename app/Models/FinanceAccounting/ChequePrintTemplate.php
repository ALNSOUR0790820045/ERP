<?php

namespace App\Models\FinanceAccounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChequePrintTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id',
        'name',
        'paper_size',
        'page_width',
        'page_height',
        'field_positions',
        'font_family',
        'font_size',
        'print_amount_words',
        'print_date',
        'print_payee',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'field_positions' => 'array',
        'page_width' => 'decimal:2',
        'page_height' => 'decimal:2',
        'print_amount_words' => 'boolean',
        'print_date' => 'boolean',
        'print_payee' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    // Default field positions for standard cheque layout
    public static function getDefaultFieldPositions(): array
    {
        return [
            'date' => ['x' => 450, 'y' => 30, 'width' => 100],
            'payee' => ['x' => 80, 'y' => 70, 'width' => 400],
            'amount_numeric' => ['x' => 450, 'y' => 70, 'width' => 100],
            'amount_words' => ['x' => 80, 'y' => 100, 'width' => 500],
            'memo' => ['x' => 80, 'y' => 180, 'width' => 200],
        ];
    }
}
