<?php

namespace App\Filament\Resources\ResourceCalendarResource\Pages;

use App\Filament\Resources\ResourceCalendarResource;
use Filament\Resources\Pages\CreateRecord;

class CreateResourceCalendar extends CreateRecord
{
    protected static string $resource = ResourceCalendarResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
