<?php

namespace App\Filament\Resources\BimModelResource\Pages;

use App\Filament\Resources\BimModelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBimModel extends CreateRecord
{
    protected static string $resource = BimModelResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
