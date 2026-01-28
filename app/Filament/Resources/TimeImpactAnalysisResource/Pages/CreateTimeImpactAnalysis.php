<?php

namespace App\Filament\Resources\TimeImpactAnalysisResource\Pages;

use App\Filament\Resources\TimeImpactAnalysisResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTimeImpactAnalysis extends CreateRecord
{
    protected static string $resource = TimeImpactAnalysisResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
