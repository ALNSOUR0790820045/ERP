<?php

namespace App\Filament\Resources\GanttTaskResource\Pages;

use App\Filament\Resources\GanttTaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGanttTask extends CreateRecord
{
    protected static string $resource = GanttTaskResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
