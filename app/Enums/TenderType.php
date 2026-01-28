<?php

namespace App\Enums;

enum TenderType: string
{
    case OPEN = 'open';
    case LIMITED = 'limited';
    case TWO_STAGE = 'two_stage';
    case PREQUALIFICATION = 'prequalification';
    case RFQ = 'rfq';
    case PRIVATE = 'private';

    public function label(): string
    {
        return match($this) {
            self::OPEN => 'عطاء عام',
            self::LIMITED => 'عطاء محدود',
            self::TWO_STAGE => 'عطاء على مرحلتين',
            self::PREQUALIFICATION => 'تأهيل مسبق',
            self::RFQ => 'طلب عروض أسعار',
            self::PRIVATE => 'مناقصة خاصة',
        };
    }
}
