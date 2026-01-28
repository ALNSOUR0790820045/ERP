<?php

namespace App\Filament\Resources\BimModelResource\Pages;

use App\Filament\Resources\BimModelResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListBimModels extends ListRecords
{
    protected static string $resource = BimModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('رفع نموذج جديد'),
        ];
    }
}
