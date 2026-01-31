<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Role;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => !$this->record->isSuperAdmin()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // تحميل الأدوار الحالية
        $data['job_roles'] = $this->record->roles()
            ->where('type', Role::TYPE_JOB)
            ->pluck('roles.id')
            ->toArray();
            
        $data['tender_roles'] = $this->record->roles()
            ->where('type', Role::TYPE_TENDER)
            ->pluck('roles.id')
            ->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        // تحديث الأدوار المتعددة
        $jobRoles = $this->data['job_roles'] ?? [];
        $tenderRoles = $this->data['tender_roles'] ?? [];
        
        $allRoles = array_merge($jobRoles, $tenderRoles);
        
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
        
        // تحديث role_id للتوافق
        if (!empty($jobRoles)) {
            $this->record->update(['role_id' => $jobRoles[0]]);
        } elseif (!empty($tenderRoles)) {
            $this->record->update(['role_id' => $tenderRoles[0]]);
        }
    }
}
