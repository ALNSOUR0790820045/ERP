<?php

namespace App\Filament\Resources\P6IntegrationResource\Pages;

use App\Filament\Resources\P6IntegrationResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListP6Integrations extends ListRecords
{
    protected static string $resource = P6IntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('عملية جديدة'),
        ];
    }
}
