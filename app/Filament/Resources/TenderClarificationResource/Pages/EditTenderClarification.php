<?php

namespace App\Filament\Resources\TenderClarificationResource\Pages;

use App\Filament\Resources\TenderClarificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderClarification extends EditRecord
{
    protected static string $resource = TenderClarificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
