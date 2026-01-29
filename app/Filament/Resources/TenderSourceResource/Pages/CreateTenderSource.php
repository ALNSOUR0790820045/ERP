<?php

namespace App\Filament\Resources\TenderSourceResource\Pages;

use App\Filament\Resources\TenderSourceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenderSource extends CreateRecord
{
    protected static string $resource = TenderSourceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
