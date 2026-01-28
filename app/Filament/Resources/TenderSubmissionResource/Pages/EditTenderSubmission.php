<?php

namespace App\Filament\Resources\TenderSubmissionResource\Pages;

use App\Filament\Resources\TenderSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderSubmission extends EditRecord
{
    protected static string $resource = TenderSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
