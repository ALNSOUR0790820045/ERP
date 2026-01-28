<?php

namespace App\Filament\Resources\TenderAnnouncementResource\Pages;

use App\Filament\Resources\TenderAnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderAnnouncement extends EditRecord
{
    protected static string $resource = TenderAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
