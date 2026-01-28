<?php

namespace App\Filament\Resources\SupplierComplianceCheckResource\Pages;

use App\Filament\Resources\SupplierComplianceCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplierComplianceCheck extends EditRecord
{
    protected static string $resource = SupplierComplianceCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
