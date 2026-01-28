<?php

namespace App\Filament\Resources\LetterOfCreditResource\Pages;

use App\Filament\Resources\LetterOfCreditResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLetterOfCredit extends CreateRecord
{
    protected static string $resource = LetterOfCreditResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = 'issued';
        $data['utilized_amount'] = 0;
        $data['available_amount'] = $data['lc_amount'];

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
