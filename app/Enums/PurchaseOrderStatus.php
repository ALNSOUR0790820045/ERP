<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PurchaseOrderStatus: string implements HasLabel, HasColor
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case SENT = 'sent';
    case PARTIAL = 'partial';
    case RECEIVED = 'received';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): ?string
    {
        return match($this) {
            self::DRAFT => 'مسودة',
            self::PENDING => 'قيد الاعتماد',
            self::APPROVED => 'معتمد',
            self::SENT => 'مرسل للمورد',
            self::PARTIAL => 'استلام جزئي',
            self::RECEIVED => 'تم الاستلام',
            self::COMPLETED => 'مكتمل',
            self::CANCELLED => 'ملغي',
        };
    }

    public function getColor(): string|array|null
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::SENT => 'info',
            self::PARTIAL => 'warning',
            self::RECEIVED => 'success',
            self::COMPLETED => 'primary',
            self::CANCELLED => 'danger',
        };
    }
}
