<?php

namespace App\Filament\Resources\TenderPurchaseApprovalResource\Pages;

use App\Filament\Resources\TenderPurchaseApprovalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenderPurchaseApproval extends CreateRecord
{
    protected static string $resource = TenderPurchaseApprovalResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_by'] = auth()->id();
        return $data;
    }
}
