<?php

namespace App\Filament\Resources\GanttChartResource\Pages;

use App\Filament\Resources\GanttChartResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGanttChart extends EditRecord
{
    protected static string $resource = GanttChartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
