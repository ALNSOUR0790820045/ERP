<?php

namespace App\Filament\Resources\SupplierRiskResource\Pages;

use App\Filament\Resources\SupplierRiskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierRisks extends ListRecords
{
    protected static string $resource = SupplierRiskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
