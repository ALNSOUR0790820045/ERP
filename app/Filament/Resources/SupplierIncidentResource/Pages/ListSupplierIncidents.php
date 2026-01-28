<?php

namespace App\Filament\Resources\SupplierIncidentResource\Pages;

use App\Filament\Resources\SupplierIncidentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierIncidents extends ListRecords
{
    protected static string $resource = SupplierIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
