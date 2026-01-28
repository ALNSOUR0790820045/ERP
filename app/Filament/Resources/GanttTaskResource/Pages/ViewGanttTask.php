<?php

namespace App\Filament\Resources\GanttTaskResource\Pages;

use App\Filament\Resources\GanttTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGanttTask extends ViewRecord
{
    protected static string $resource = GanttTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
