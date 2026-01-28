<?php

namespace App\Enums;

enum SubmissionMethod: string
{
    case HAND = 'hand';
    case MAIL = 'mail';
    case ELECTRONIC = 'electronic';

    public function label(): string
    {
        return match($this) {
            self::HAND => 'تسليم يدوي',
            self::MAIL => 'بريد مسجل',
            self::ELECTRONIC => 'إلكتروني',
        };
    }
}
