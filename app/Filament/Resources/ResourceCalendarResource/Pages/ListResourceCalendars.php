<?php

namespace App\Filament\Resources\ResourceCalendarResource\Pages;

use App\Filament\Resources\ResourceCalendarResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListResourceCalendars extends ListRecords
{
    protected static string $resource = ResourceCalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('تقويم جديد'),
        ];
    }
}
