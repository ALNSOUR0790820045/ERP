<?php

namespace App\Filament\Resources\SupplierCertificationResource\Pages;

use App\Filament\Resources\SupplierCertificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplierCertification extends EditRecord
{
    protected static string $resource = SupplierCertificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
