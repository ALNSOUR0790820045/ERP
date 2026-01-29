<?php

namespace App\Filament\Resources\TenderEvaluationResource\Pages;

use App\Filament\Resources\TenderEvaluationResource;
use Filament\Resources\Pages\ListRecords;

class ListTenderEvaluations extends ListRecords
{
    protected static string $resource = TenderEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
