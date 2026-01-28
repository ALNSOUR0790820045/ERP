<?php

namespace App\Filament\Resources\P6IntegrationResource\Pages;

use App\Filament\Resources\P6IntegrationResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewP6Integration extends ViewRecord
{
    protected static string $resource = P6IntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
