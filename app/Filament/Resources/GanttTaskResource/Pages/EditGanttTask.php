<?php

namespace App\Filament\Resources\GanttTaskResource\Pages;

use App\Filament\Resources\GanttTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGanttTask extends EditRecord
{
    protected static string $resource = GanttTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
