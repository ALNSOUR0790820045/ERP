<?php

namespace App\Filament\Resources\TenderBidDataSheetResource\Pages;

use App\Filament\Resources\TenderBidDataSheetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderBidDataSheet extends EditRecord
{
    protected static string $resource = TenderBidDataSheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
