<?php

namespace App\Filament\Resources\TenderProposalClosureResource\Pages;

use App\Filament\Resources\TenderProposalClosureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenderProposalClosures extends ListRecords
{
    protected static string $resource = TenderProposalClosureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
