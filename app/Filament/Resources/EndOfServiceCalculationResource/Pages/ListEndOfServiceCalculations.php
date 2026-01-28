<?php

namespace App\Filament\Resources\EndOfServiceCalculationResource\Pages;

use App\Filament\Resources\EndOfServiceCalculationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEndOfServiceCalculations extends ListRecords
{
    protected static string $resource = EndOfServiceCalculationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
