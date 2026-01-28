<?php

namespace App\Filament\Resources\SupplierLicenseResource\Pages;

use App\Filament\Resources\SupplierLicenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierLicenses extends ListRecords
{
    protected static string $resource = SupplierLicenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
