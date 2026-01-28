<?php

namespace App\Filament\Resources\TenderSubmissionResource\Pages;

use App\Filament\Resources\TenderSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenderSubmissions extends ListRecords
{
    protected static string $resource = TenderSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
