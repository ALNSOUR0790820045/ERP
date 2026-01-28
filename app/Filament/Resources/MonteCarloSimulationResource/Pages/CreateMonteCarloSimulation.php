<?php

namespace App\Filament\Resources\MonteCarloSimulationResource\Pages;

use App\Filament\Resources\MonteCarloSimulationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMonteCarloSimulation extends CreateRecord
{
    protected static string $resource = MonteCarloSimulationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
