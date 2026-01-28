<?php

namespace App\Filament\Resources\ChequeReceivedResource\Pages;

use App\Filament\Resources\ChequeReceivedResource;
use Filament\Resources\Pages\CreateRecord;

class CreateChequeReceived extends CreateRecord
{
    protected static string $resource = ChequeReceivedResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = 'received';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
