<?php

namespace App\Filament\Resources\ChequeIssuedResource\Pages;

use App\Filament\Resources\ChequeIssuedResource;
use App\Models\FinanceAccounting\ChequeBook;
use Filament\Resources\Pages\CreateRecord;

class CreateChequeIssued extends CreateRecord
{
    protected static string $resource = ChequeIssuedResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = 'draft';

        // Increment cheque book counter
        if (isset($data['cheque_book_id'])) {
            $chequeBook = ChequeBook::find($data['cheque_book_id']);
            $chequeBook->incrementChequeNumber();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
