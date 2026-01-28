<?php

namespace App\Filament\Resources\EvmMeasurementResource\Pages;

use App\Filament\Resources\EvmMeasurementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEvmMeasurement extends EditRecord
{
    protected static string $resource = EvmMeasurementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
