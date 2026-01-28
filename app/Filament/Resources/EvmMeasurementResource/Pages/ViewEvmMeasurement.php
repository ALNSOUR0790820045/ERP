<?php

namespace App\Filament\Resources\EvmMeasurementResource\Pages;

use App\Filament\Resources\EvmMeasurementResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEvmMeasurement extends ViewRecord
{
    protected static string $resource = EvmMeasurementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
