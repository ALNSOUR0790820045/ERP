<?php

namespace App\Filament\Resources\TenderTechnicalProposalResource\Pages;

use App\Filament\Resources\TenderTechnicalProposalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderTechnicalProposal extends EditRecord
{
    protected static string $resource = TenderTechnicalProposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
