<?php

namespace App\Filament\Resources\BimModelResource\Pages;

use App\Filament\Resources\BimModelResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditBimModel extends EditRecord
{
    protected static string $resource = BimModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
