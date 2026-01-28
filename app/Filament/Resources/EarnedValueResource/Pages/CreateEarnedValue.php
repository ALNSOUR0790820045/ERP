<?php

namespace App\Filament\Resources\EarnedValueResource\Pages;

use App\Filament\Resources\EarnedValueResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEarnedValue extends CreateRecord
{
    protected static string $resource = EarnedValueResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Calculate variance and performance indices
        $data['sv'] = ($data['ev'] ?? 0) - ($data['pv'] ?? 0);
        $data['cv'] = ($data['ev'] ?? 0) - ($data['ac'] ?? 0);
        $data['spi'] = $data['pv'] > 0 ? ($data['ev'] / $data['pv']) : 0;
        $data['cpi'] = $data['ac'] > 0 ? ($data['ev'] / $data['ac']) : 0;
        
        // Calculate EAC, ETC, VAC
        if ($data['cpi'] > 0) {
            $data['eac'] = $data['bac'] / $data['cpi'];
            $data['etc'] = $data['eac'] - $data['ac'];
            $data['vac'] = $data['bac'] - $data['eac'];
        }
        
        // Calculate TCPI
        $remainingWork = ($data['bac'] ?? 0) - ($data['ev'] ?? 0);
        $remainingBudget = ($data['bac'] ?? 0) - ($data['ac'] ?? 0);
        $data['tcpi'] = $remainingBudget > 0 ? ($remainingWork / $remainingBudget) : 0;

        return $data;
    }
}
