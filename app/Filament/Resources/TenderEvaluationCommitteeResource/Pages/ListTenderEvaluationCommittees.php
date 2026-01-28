<?php

namespace App\Filament\Resources\TenderEvaluationCommitteeResource\Pages;

use App\Filament\Resources\TenderEvaluationCommitteeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenderEvaluationCommittees extends ListRecords
{
    protected static string $resource = TenderEvaluationCommitteeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
