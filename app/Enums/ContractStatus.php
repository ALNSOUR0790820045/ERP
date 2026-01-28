<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ContractStatus: string implements HasLabel, HasColor
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case SIGNED = 'signed';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case COMPLETED = 'completed';
    case TERMINATED = 'terminated';
    case CLOSED = 'closed';

    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'مسودة',
            self::PENDING_APPROVAL => 'بانتظار الموافقة',
            self::APPROVED => 'معتمد',
            self::SIGNED => 'موقع',
            self::ACTIVE => 'قيد التنفيذ',
            self::SUSPENDED => 'موقوف',
            self::COMPLETED => 'مكتمل',
            self::TERMINATED => 'منتهي',
            self::CLOSED => 'مغلق',
        };
    }

    public function getColor(): string|array|null
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::PENDING_APPROVAL => 'warning',
            self::APPROVED => 'info',
            self::SIGNED => 'primary',
            self::ACTIVE => 'success',
            self::SUSPENDED => 'danger',
            self::COMPLETED => 'success',
            self::TERMINATED => 'danger',
            self::CLOSED => 'gray',
        };
    }

    public function canEdit(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING_APPROVAL]);
    }

    public function canAddVariation(): bool
    {
        return in_array($this, [self::ACTIVE, self::SIGNED]);
    }
}
