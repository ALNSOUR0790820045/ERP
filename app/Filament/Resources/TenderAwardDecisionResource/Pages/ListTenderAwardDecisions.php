<?php

namespace App\Filament\Resources\TenderAwardDecisionResource\Pages;

use App\Filament\Resources\TenderAwardDecisionResource;
use Filament\Resources\Pages\ListRecords;

class ListTenderAwardDecisions extends ListRecords
{
    protected static string $resource = TenderAwardDecisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
