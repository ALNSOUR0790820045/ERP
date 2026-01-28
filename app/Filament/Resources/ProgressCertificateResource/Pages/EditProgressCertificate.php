<?php

namespace App\Filament\Resources\ProgressCertificateResource\Pages;

use App\Filament\Resources\ProgressCertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProgressCertificate extends EditRecord
{
    protected static string $resource = ProgressCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // إعادة حساب القيم
        $data['current_work_done'] = ($data['cumulative_work_done'] ?? 0) - ($data['previous_work_done'] ?? 0);
        $data['gross_amount'] = ($data['cumulative_work_done'] ?? 0) + ($data['materials_on_site'] ?? 0);
        
        $data['retention_amount'] = ($data['cumulative_work_done'] ?? 0) * (($data['retention_rate'] ?? 10) / 100);
        $data['current_retention'] = ($data['retention_amount'] ?? 0) - ($data['previous_retention'] ?? 0);
        
        $data['current_advance_recovery'] = ($data['advance_recovery'] ?? 0) - ($data['previous_advance_recovery'] ?? 0);
        
        $data['total_deductions'] = ($data['retention_amount'] ?? 0) + ($data['advance_recovery'] ?? 0) + ($data['other_deductions'] ?? 0);
        $data['net_amount'] = ($data['gross_amount'] ?? 0) - ($data['total_deductions'] ?? 0);
        $data['current_net'] = ($data['net_amount'] ?? 0) - ($data['previous_net'] ?? 0);
        
        $data['vat_amount'] = ($data['current_net'] ?? 0) * (($data['vat_rate'] ?? 16) / 100);
        $data['final_amount'] = ($data['current_net'] ?? 0) + ($data['vat_amount'] ?? 0);

        return $data;
    }
}
