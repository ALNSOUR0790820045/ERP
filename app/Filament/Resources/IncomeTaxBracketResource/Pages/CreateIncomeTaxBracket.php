<?php

namespace App\Filament\Resources\IncomeTaxBracketResource\Pages;

use App\Filament\Resources\IncomeTaxBracketResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIncomeTaxBracket extends CreateRecord
{
    protected static string $resource = IncomeTaxBracketResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
