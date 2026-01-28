<?php

namespace App\Filament\Resources\SupplierLicenseResource\Pages;

use App\Filament\Resources\SupplierLicenseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplierLicense extends EditRecord
{
    protected static string $resource = SupplierLicenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
