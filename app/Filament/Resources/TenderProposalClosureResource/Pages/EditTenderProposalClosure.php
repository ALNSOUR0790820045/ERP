<?php

namespace App\Filament\Resources\TenderProposalClosureResource\Pages;

use App\Filament\Resources\TenderProposalClosureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderProposalClosure extends EditRecord
{
    protected static string $resource = TenderProposalClosureResource::class;

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
