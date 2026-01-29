<?php

namespace App\Filament\Resources\TenderStageLogResource\Pages;

use App\Filament\Resources\TenderStageLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenderStageLog extends CreateRecord
{
    protected static string $resource = TenderStageLogResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
