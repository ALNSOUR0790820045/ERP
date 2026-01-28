<?php

namespace App\Filament\Resources\SupplierQualificationResource\Pages;

use App\Filament\Resources\SupplierQualificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierQualifications extends ListRecords
{
    protected static string $resource = SupplierQualificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
