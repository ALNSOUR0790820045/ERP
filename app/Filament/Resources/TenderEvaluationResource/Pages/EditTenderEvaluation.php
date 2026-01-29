<?php

namespace App\Filament\Resources\TenderEvaluationResource\Pages;

use App\Filament\Resources\TenderEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderEvaluation extends EditRecord
{
    protected static string $resource = TenderEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
