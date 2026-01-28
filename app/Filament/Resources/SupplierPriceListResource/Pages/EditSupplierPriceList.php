<?php

namespace App\Filament\Resources\SupplierPriceListResource\Pages;

use App\Filament\Resources\SupplierPriceListResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplierPriceList extends EditRecord
{
    protected static string $resource = SupplierPriceListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
