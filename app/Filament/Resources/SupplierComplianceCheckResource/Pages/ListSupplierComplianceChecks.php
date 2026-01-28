<?php

namespace App\Filament\Resources\SupplierComplianceCheckResource\Pages;

use App\Filament\Resources\SupplierComplianceCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierComplianceChecks extends ListRecords
{
    protected static string $resource = SupplierComplianceCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
