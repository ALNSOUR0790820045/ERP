<?php

namespace App\Filament\Resources\SupplierKpiResource\Pages;

use App\Filament\Resources\SupplierKpiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierKpis extends ListRecords
{
    protected static string $resource = SupplierKpiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
