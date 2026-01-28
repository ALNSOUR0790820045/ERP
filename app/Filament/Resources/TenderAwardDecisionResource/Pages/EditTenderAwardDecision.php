<?php

namespace App\Filament\Resources\TenderAwardDecisionResource\Pages;

use App\Filament\Resources\TenderAwardDecisionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderAwardDecision extends EditRecord
{
    protected static string $resource = TenderAwardDecisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
