<?php

namespace App\Filament\Resources\SupplierAuditResource\Pages;

use App\Filament\Resources\SupplierAuditResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierAudits extends ListRecords
{
    protected static string $resource = SupplierAuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
