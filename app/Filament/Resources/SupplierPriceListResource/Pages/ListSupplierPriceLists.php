<?php

namespace App\Filament\Resources\SupplierPriceListResource\Pages;

use App\Filament\Resources\SupplierPriceListResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierPriceLists extends ListRecords
{
    protected static string $resource = SupplierPriceListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
