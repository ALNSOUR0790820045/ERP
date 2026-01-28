<?php

namespace App\Filament\Resources\IncomeTaxBracketResource\Pages;

use App\Filament\Resources\IncomeTaxBracketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIncomeTaxBracket extends EditRecord
{
    protected static string $resource = IncomeTaxBracketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
