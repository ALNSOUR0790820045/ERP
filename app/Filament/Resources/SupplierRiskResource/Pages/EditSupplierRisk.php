<?php

namespace App\Filament\Resources\SupplierRiskResource\Pages;

use App\Filament\Resources\SupplierRiskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplierRisk extends EditRecord
{
    protected static string $resource = SupplierRiskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
