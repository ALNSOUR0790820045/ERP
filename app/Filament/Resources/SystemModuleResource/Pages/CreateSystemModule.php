<?php

namespace App\Filament\Resources\SystemModuleResource\Pages;

use App\Filament\Resources\SystemModuleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSystemModule extends CreateRecord
{
    protected static string $resource = SystemModuleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
