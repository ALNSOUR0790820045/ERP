<?php

namespace App\Filament\Resources\ExtensionOfTimeResource\Pages;

use App\Filament\Resources\ExtensionOfTimeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExtensionOfTime extends CreateRecord
{
    protected static string $resource = ExtensionOfTimeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
