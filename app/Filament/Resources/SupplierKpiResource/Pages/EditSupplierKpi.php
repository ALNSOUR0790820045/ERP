<?php

namespace App\Filament\Resources\SupplierKpiResource\Pages;

use App\Filament\Resources\SupplierKpiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplierKpi extends EditRecord
{
    protected static string $resource = SupplierKpiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
