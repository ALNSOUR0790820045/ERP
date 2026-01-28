<?php

namespace App\Filament\Resources\TimeImpactAnalysisResource\Pages;

use App\Filament\Resources\TimeImpactAnalysisResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewTimeImpactAnalysis extends ViewRecord
{
    protected static string $resource = TimeImpactAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
