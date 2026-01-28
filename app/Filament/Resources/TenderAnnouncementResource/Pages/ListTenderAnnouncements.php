<?php

namespace App\Filament\Resources\TenderAnnouncementResource\Pages;

use App\Filament\Resources\TenderAnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenderAnnouncements extends ListRecords
{
    protected static string $resource = TenderAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
