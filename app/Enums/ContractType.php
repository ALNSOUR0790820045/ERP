<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ContractType: string implements HasLabel
{
    case CONSTRUCTION = 'construction';
    case DESIGN_BUILD = 'design_build';
    case EPC = 'epc';
    case MAINTENANCE = 'maintenance';
    case SUPPLY = 'supply';
    case CONSULTANCY = 'consultancy';
    case EQUIPMENT_RENTAL = 'equipment_rental';
    case SUBCONTRACT = 'subcontract';

    public function getLabel(): string
    {
        return match($this) {
            self::CONSTRUCTION => 'عقد إنشاءات',
            self::DESIGN_BUILD => 'تصميم وبناء',
            self::EPC => 'عقد EPC',
            self::MAINTENANCE => 'عقد صيانة',
            self::SUPPLY => 'عقد توريد',
            self::CONSULTANCY => 'عقد استشارات',
            self::EQUIPMENT_RENTAL => 'إيجار معدات',
            self::SUBCONTRACT => 'مقاولة باطن',
        };
    }
}
