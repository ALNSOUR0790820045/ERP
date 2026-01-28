<?php

namespace App\Filament\Resources\SupplierCertificationResource\Pages;

use App\Filament\Resources\SupplierCertificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierCertifications extends ListRecords
{
    protected static string $resource = SupplierCertificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
