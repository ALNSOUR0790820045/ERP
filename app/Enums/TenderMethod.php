<?php

namespace App\Enums;

enum TenderMethod: string
{
    case PUBLIC = 'public';
    case LIMITED = 'limited';
    case DIRECT = 'direct';

    public function label(): string
    {
        return match($this) {
            self::PUBLIC => 'علني',
            self::LIMITED => 'محدود',
            self::DIRECT => 'مباشر',
        };
    }
}
