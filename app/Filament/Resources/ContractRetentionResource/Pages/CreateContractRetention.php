<?php

namespace App\Filament\Resources\ContractRetentionResource\Pages;

use App\Filament\Resources\ContractRetentionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContractRetention extends CreateRecord
{
    protected static string $resource = ContractRetentionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
