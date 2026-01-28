<?php

namespace App\Filament\Resources\P6IntegrationResource\Pages;

use App\Filament\Resources\P6IntegrationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateP6Integration extends CreateRecord
{
    protected static string $resource = P6IntegrationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
