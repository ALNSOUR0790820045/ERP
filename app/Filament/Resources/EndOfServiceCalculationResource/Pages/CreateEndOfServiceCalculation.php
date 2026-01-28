<?php

namespace App\Filament\Resources\EndOfServiceCalculationResource\Pages;

use App\Filament\Resources\EndOfServiceCalculationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEndOfServiceCalculation extends CreateRecord
{
    protected static string $resource = EndOfServiceCalculationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // حساب مدة الخدمة
        if (!empty($data['hire_date']) && !empty($data['termination_date'])) {
            $hireDate = \Carbon\Carbon::parse($data['hire_date']);
            $terminationDate = \Carbon\Carbon::parse($data['termination_date']);
            $diff = $hireDate->diff($terminationDate);
            
            $data['service_years'] = $diff->y;
            $data['service_months'] = $diff->m;
            $data['service_days'] = $diff->d;
            $data['total_service_years'] = $hireDate->diffInDays($terminationDate) / 365.25;
        }

        $data['calculated_by'] = auth()->id();
        
        return $data;
    }
}
