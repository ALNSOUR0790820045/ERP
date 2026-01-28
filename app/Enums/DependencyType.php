<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DependencyType: string implements HasLabel
{
    case FS = 'FS';
    case SS = 'SS';
    case FF = 'FF';
    case SF = 'SF';

    public function getLabel(): ?string
    {
        return match($this) {
            self::FS => 'انتهاء-بداية (FS)',
            self::SS => 'بداية-بداية (SS)',
            self::FF => 'انتهاء-انتهاء (FF)',
            self::SF => 'بداية-انتهاء (SF)',
        };
    }
}
