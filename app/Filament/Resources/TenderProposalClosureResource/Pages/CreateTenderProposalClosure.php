<?php

namespace App\Filament\Resources\TenderProposalClosureResource\Pages;

use App\Filament\Resources\TenderProposalClosureResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenderProposalClosure extends CreateRecord
{
    protected static string $resource = TenderProposalClosureResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
