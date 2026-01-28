<?php

namespace App\Filament\Resources\EvmMeasurementResource\Pages;

use App\Filament\Resources\EvmMeasurementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEvmMeasurements extends ListRecords
{
    protected static string $resource = EvmMeasurementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
