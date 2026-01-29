<?php

namespace App\Filament\Resources\TenderStageLogResource\Pages;

use App\Filament\Resources\TenderStageLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderStageLog extends EditRecord
{
    protected static string $resource = TenderStageLogResource::class;

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
