<?php

namespace App\Filament\Resources\MonteCarloSimulationResource\Pages;

use App\Filament\Resources\MonteCarloSimulationResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditMonteCarloSimulation extends EditRecord
{
    protected static string $resource = MonteCarloSimulationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
