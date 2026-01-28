<?php

namespace App\Filament\Resources\TenderTechnicalProposalResource\Pages;

use App\Filament\Resources\TenderTechnicalProposalResource;
use Filament\Resources\Pages\ListRecords;

class ListTenderTechnicalProposals extends ListRecords
{
    protected static string $resource = TenderTechnicalProposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
