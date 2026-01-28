<?php

namespace App\Filament\Resources\FinalAccountResource\Pages;

use App\Filament\Resources\FinalAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinalAccounts extends ListRecords
{
    protected static string $resource = FinalAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
