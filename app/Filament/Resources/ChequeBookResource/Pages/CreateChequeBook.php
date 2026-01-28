<?php

namespace App\Filament\Resources\ChequeBookResource\Pages;

use App\Filament\Resources\ChequeBookResource;
use Filament\Resources\Pages\CreateRecord;

class CreateChequeBook extends CreateRecord
{
    protected static string $resource = ChequeBookResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['current_number'] = $data['start_number'];
        $data['total_cheques'] = $data['end_number'] - $data['start_number'] + 1;
        $data['used_cheques'] = 0;
        $data['cancelled_cheques'] = 0;
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
