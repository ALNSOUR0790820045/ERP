<?php

namespace App\Filament\Resources\SupplierAuditResource\Pages;

use App\Filament\Resources\SupplierAuditResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplierAudit extends EditRecord
{
    protected static string $resource = SupplierAuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
