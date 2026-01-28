<?php

namespace App\Filament\Resources\ResourceCalendarResource\Pages;

use App\Filament\Resources\ResourceCalendarResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewResourceCalendar extends ViewRecord
{
    protected static string $resource = ResourceCalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
