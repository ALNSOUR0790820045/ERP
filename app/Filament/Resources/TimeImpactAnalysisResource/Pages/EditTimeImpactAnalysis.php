<?php

namespace App\Filament\Resources\TimeImpactAnalysisResource\Pages;

use App\Filament\Resources\TimeImpactAnalysisResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditTimeImpactAnalysis extends EditRecord
{
    protected static string $resource = TimeImpactAnalysisResource::class;

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
