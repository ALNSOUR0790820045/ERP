<?php

namespace App\Filament\Resources\TimeImpactAnalysisResource\Pages;

use App\Filament\Resources\TimeImpactAnalysisResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListTimeImpactAnalyses extends ListRecords
{
    protected static string $resource = TimeImpactAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('تحليل جديد'),
        ];
    }
}
