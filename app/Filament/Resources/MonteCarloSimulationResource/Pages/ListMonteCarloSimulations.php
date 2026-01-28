<?php

namespace App\Filament\Resources\MonteCarloSimulationResource\Pages;

use App\Filament\Resources\MonteCarloSimulationResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListMonteCarloSimulations extends ListRecords
{
    protected static string $resource = MonteCarloSimulationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('محاكاة جديدة'),
        ];
    }
}
