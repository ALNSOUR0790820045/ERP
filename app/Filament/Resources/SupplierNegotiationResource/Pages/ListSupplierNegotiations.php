<?php

namespace App\Filament\Resources\SupplierNegotiationResource\Pages;

use App\Filament\Resources\SupplierNegotiationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierNegotiations extends ListRecords
{
    protected static string $resource = SupplierNegotiationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
