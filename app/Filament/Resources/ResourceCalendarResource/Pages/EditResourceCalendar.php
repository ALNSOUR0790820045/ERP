<?php

namespace App\Filament\Resources\ResourceCalendarResource\Pages;

use App\Filament\Resources\ResourceCalendarResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditResourceCalendar extends EditRecord
{
    protected static string $resource = ResourceCalendarResource::class;

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
