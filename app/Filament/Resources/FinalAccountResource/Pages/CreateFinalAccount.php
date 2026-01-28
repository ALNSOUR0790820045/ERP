<?php

namespace App\Filament\Resources\FinalAccountResource\Pages;

use App\Filament\Resources\FinalAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFinalAccount extends CreateRecord
{
    protected static string $resource = FinalAccountResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
