<?php

namespace App\Filament\Resources\SupplierRiskAssessmentResource\Pages;

use App\Filament\Resources\SupplierRiskAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplierRiskAssessment extends EditRecord
{
    protected static string $resource = SupplierRiskAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
