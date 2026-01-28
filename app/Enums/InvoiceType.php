<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceType: string implements HasLabel, HasColor
{
    case INTERIM = 'interim';
    case ADVANCE = 'advance';
    case PROVISIONAL = 'provisional';
    case FINAL = 'final';
    case CLAIM = 'claim';

    public function getLabel(): ?string
    {
        return match($this) {
            self::INTERIM => 'مستخلص دوري',
            self::ADVANCE => 'دفعة مقدمة',
            self::PROVISIONAL => 'استلام ابتدائي',
            self::FINAL => 'حساب ختامي',
            self::CLAIM => 'مطالبة',
        };
    }

    public function getColor(): string|array|null
    {
        return match($this) {
            self::INTERIM => 'info',
            self::ADVANCE => 'warning',
            self::PROVISIONAL => 'success',
            self::FINAL => 'primary',
            self::CLAIM => 'danger',
        };
    }
}
