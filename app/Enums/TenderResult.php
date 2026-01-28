<?php

namespace App\Enums;

enum TenderResult: string
{
    case PENDING = 'pending';
    case WON = 'won';
    case LOST = 'lost';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'بانتظار النتيجة',
            self::WON => 'فوز',
            self::LOST => 'خسارة',
            self::CANCELLED => 'ملغي',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::WON => 'success',
            self::LOST => 'danger',
            self::CANCELLED => 'gray',
        };
    }
}
