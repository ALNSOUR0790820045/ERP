<?php

namespace App\Enums;

enum BondType: string
{
    case BANK_GUARANTEE = 'bank_guarantee';
    case CERTIFIED_CHECK = 'certified_check';
    case REGULAR_CHECK = 'regular_check';
    case GUARANTEE_OR_CERTIFIED = 'guarantee_or_certified';
    case ANY = 'any';
    case TRANSFER = 'transfer';
    case CASH = 'cash';

    public function label(): string
    {
        return match($this) {
            self::BANK_GUARANTEE => 'كفالة بنكية',
            self::CERTIFIED_CHECK => 'شيك مصدق',
            self::REGULAR_CHECK => 'شيك بنكي عادي',
            self::GUARANTEE_OR_CERTIFIED => 'كفالة بنكية أو شيك مصدق',
            self::ANY => 'أي شكل من أشكال التأمين',
            self::TRANSFER => 'تحويل بنكي',
            self::CASH => 'نقداً',
        };
    }
}
