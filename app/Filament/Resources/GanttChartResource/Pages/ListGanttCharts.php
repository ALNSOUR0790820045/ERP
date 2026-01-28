<?php

namespace App\Filament\Resources\GanttChartResource\Pages;

use App\Filament\Resources\GanttChartResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGanttCharts extends ListRecords
{
    protected static string $resource = GanttChartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
