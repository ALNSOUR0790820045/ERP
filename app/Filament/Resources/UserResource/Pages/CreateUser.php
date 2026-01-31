<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // ربط الأدوار المتعددة
        $jobRoles = $this->data['job_roles'] ?? [];
        $tenderRoles = $this->data['tender_roles'] ?? [];
        
        $allRoles = array_merge($jobRoles, $tenderRoles);
        
        if (!empty($allRoles)) {
            $syncData = [];
            $isFirst = true;
            foreach ($allRoles as $roleId) {
                $syncData[$roleId] = [
                    'is_primary' => $isFirst,
                    'assigned_at' => now(),
                    'assigned_by' => auth()->id(),
                ];
                $isFirst = false;
            }
            $this->record->roles()->sync($syncData);
            
            // تحديث role_id للتوافق مع الكود القديم
            if (!empty($jobRoles)) {
                $this->record->update(['role_id' => $jobRoles[0]]);
            } elseif (!empty($tenderRoles)) {
                $this->record->update(['role_id' => $tenderRoles[0]]);
            }
        }
    }
}
