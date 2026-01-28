<?php

namespace App\Enums;

enum BondType: string
{
    case BANK_GUARANTEE = 'bank_guarantee';
    case CERTIFIED_CHECK = 'certified_check';
    case TRANSFER = 'transfer';
    case CASH = 'cash';

    public function label(): string
    {
        return match($this) {
            self::BANK_GUARANTEE => 'كفالة بنكية',
            self::CERTIFIED_CHECK => 'شيك مصدق',
            self::TRANSFER => 'تحويل بنكي',
            self::CASH => 'نقداً',
        };
    }
}
