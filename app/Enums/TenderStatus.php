<?php

namespace App\Enums;

enum TenderStatus: string
{
    case NEW = 'new';
    case STUDYING = 'studying';
    case GO = 'go';
    case NO_GO = 'no_go';
    case PRICING = 'pricing';
    case READY = 'ready';
    case SUBMITTED = 'submitted';
    case OPENING = 'opening';
    case WON = 'won';
    case LOST = 'lost';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::NEW => 'جديد',
            self::STUDYING => 'قيد الدراسة',
            self::GO => 'قرار المشاركة',
            self::NO_GO => 'عدم المشاركة',
            self::PRICING => 'قيد التسعير',
            self::READY => 'جاهز للتقديم',
            self::SUBMITTED => 'تم التقديم',
            self::OPENING => 'بانتظار الفتح',
            self::WON => 'فوز',
            self::LOST => 'خسارة',
            self::CANCELLED => 'ملغي',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::NEW => 'info',
            self::STUDYING => 'warning',
            self::GO => 'success',
            self::NO_GO => 'danger',
            self::PRICING => 'warning',
            self::READY => 'primary',
            self::SUBMITTED => 'info',
            self::OPENING => 'warning',
            self::WON => 'success',
            self::LOST => 'danger',
            self::CANCELLED => 'gray',
        };
    }
}
