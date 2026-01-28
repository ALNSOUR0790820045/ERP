<?php

namespace App\Filament\Resources\MonteCarloSimulationResource\Pages;

use App\Filament\Resources\MonteCarloSimulationResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewMonteCarloSimulation extends ViewRecord
{
    protected static string $resource = MonteCarloSimulationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
