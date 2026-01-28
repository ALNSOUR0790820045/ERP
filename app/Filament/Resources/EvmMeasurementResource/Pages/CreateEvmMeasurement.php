<?php

namespace App\Filament\Resources\EvmMeasurementResource\Pages;

use App\Filament\Resources\EvmMeasurementResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEvmMeasurement extends CreateRecord
{
    protected static string $resource = EvmMeasurementResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
