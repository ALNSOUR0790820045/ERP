<?php

namespace App\Filament\Resources\TenderFinancialProposalResource\Pages;

use App\Filament\Resources\TenderFinancialProposalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenderFinancialProposals extends ListRecords
{
    protected static string $resource = TenderFinancialProposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
