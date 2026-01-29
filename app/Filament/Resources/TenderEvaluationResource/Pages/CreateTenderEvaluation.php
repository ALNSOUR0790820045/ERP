<?php

namespace App\Filament\Resources\TenderEvaluationResource\Pages;

use App\Filament\Resources\TenderEvaluationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenderEvaluation extends CreateRecord
{
    protected static string $resource = TenderEvaluationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['evaluated_by'] = auth()->id();
        $data['evaluated_at'] = now();
        return $data;
    }
}
