<?php

namespace App\Filament\Resources\SupplierIncidentResource\Pages;

use App\Filament\Resources\SupplierIncidentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplierIncident extends EditRecord
{
    protected static string $resource = SupplierIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
