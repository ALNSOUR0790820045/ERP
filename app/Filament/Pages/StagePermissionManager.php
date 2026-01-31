<?php

namespace App\Filament\Pages;

use App\Models\Module;
use App\Models\ModuleStage;
use App\Models\PermissionTemplate;
use App\Models\PermissionType;
use App\Models\RoleStagePermission;
use App\Models\User;
use App\Models\UserStagePermission;
use App\Services\StagePermissionService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class StagePermissionManager extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static string $view = 'filament.pages.stage-permission-manager';
    protected static ?string $navigationLabel = 'صلاحيات المراحل';
    protected static ?string $title = 'إدارة صلاحيات المراحل';
    protected static ?string $navigationGroup = 'إدارة النظام';
    protected static ?int $navigationSort = 2;

    public ?int $selectedUserId = null;
    public ?int $selectedRoleId = null;
    public ?int $selectedModuleId = null;
    public string $activeTab = 'users'; // users or roles
    public array $permissionMatrix = [];

    public function mount(): void
    {
        // تحديد أول مستخدم افتراضياً
        $firstUser = User::first();
        if ($firstUser) {
            $this->selectedUserId = $firstUser->id;
        }

        // تحديد أول وحدة افتراضياً
        $firstModule = Module::first();
        if ($firstModule) {
            $this->selectedModuleId = $firstModule->id;
            $this->loadPermissionMatrix();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('applyTemplate')
                ->label('تطبيق قالب')
                ->icon('heroicon-o-document-duplicate')
                ->color('success')
                ->form([
                    Select::make('template_id')
                        ->label('القالب')
                        ->options(function () {
                            return PermissionTemplate::where('is_active', true)
                                ->when($this->selectedModuleId, function ($query) {
                                    $query->where('module_id', $this->selectedModuleId);
                                })
                                ->pluck('name_ar', 'id');
                        })
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $template = PermissionTemplate::find($data['template_id']);
                    if ($template) {
                        if ($this->activeTab === 'users' && $this->selectedUserId) {
                            $template->applyToUser($this->selectedUserId);
                            Notification::make()
                                ->success()
                                ->title('تم تطبيق القالب بنجاح')
                                ->send();
                        } elseif ($this->activeTab === 'roles' && $this->selectedRoleId) {
                            $template->applyToRole($this->selectedRoleId);
                            Notification::make()
                                ->success()
                                ->title('تم تطبيق القالب بنجاح')
                                ->send();
                        }
                        $this->loadPermissionMatrix();
                    }
                })
                ->visible(fn () => $this->selectedModuleId && ($this->selectedUserId || $this->selectedRoleId)),

            Action::make('clearAllPermissions')
                ->label('مسح كل الصلاحيات')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('تأكيد المسح')
                ->modalDescription('هل أنت متأكد من مسح جميع صلاحيات هذه الوحدة؟')
                ->action(function (): void {
                    if ($this->activeTab === 'users' && $this->selectedUserId) {
                        UserStagePermission::where('user_id', $this->selectedUserId)
                            ->where('module_id', $this->selectedModuleId)
                            ->delete();
                    } elseif ($this->activeTab === 'roles' && $this->selectedRoleId) {
                        RoleStagePermission::where('role_id', $this->selectedRoleId)
                            ->where('module_id', $this->selectedModuleId)
                            ->delete();
                    }
                    $this->loadPermissionMatrix();
                    Notification::make()
                        ->success()
                        ->title('تم مسح الصلاحيات')
                        ->send();
                }),
        ];
    }

    public function getUsers(): Collection
    {
        return User::orderBy('name')->get();
    }

    public function getRoles(): Collection
    {
        return Role::orderBy('name')->get();
    }

    public function getModules(): Collection
    {
        return Module::where('is_active', true)->orderBy('sort_order')->get();
    }

    public function getStages(): Collection
    {
        if (!$this->selectedModuleId) {
            return collect();
        }

        return ModuleStage::where('module_id', $this->selectedModuleId)
            ->orderBy('sort_order')
            ->get();
    }

    public function getPermissionTypes(): Collection
    {
        return PermissionType::orderBy('sort_order')->get();
    }

    public function selectUser(int $userId): void
    {
        $this->selectedUserId = $userId;
        $this->loadPermissionMatrix();
    }

    public function selectRole(int $roleId): void
    {
        $this->selectedRoleId = $roleId;
        $this->loadPermissionMatrix();
    }

    public function selectModule(int $moduleId): void
    {
        $this->selectedModuleId = $moduleId;
        $this->loadPermissionMatrix();
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->loadPermissionMatrix();
    }

    public function loadPermissionMatrix(): void
    {
        $this->permissionMatrix = [];

        if (!$this->selectedModuleId) {
            return;
        }

        $stages = $this->getStages();
        $permissionTypes = $this->getPermissionTypes();

        foreach ($stages as $stage) {
            foreach ($permissionTypes as $type) {
                $key = "{$stage->id}_{$type->id}";
                
                if ($this->activeTab === 'users' && $this->selectedUserId) {
                    $permission = UserStagePermission::where('user_id', $this->selectedUserId)
                        ->where('stage_id', $stage->id)
                        ->where('permission_type_id', $type->id)
                        ->first();
                    $this->permissionMatrix[$key] = $permission ? true : false;
                } elseif ($this->activeTab === 'roles' && $this->selectedRoleId) {
                    $permission = RoleStagePermission::where('role_id', $this->selectedRoleId)
                        ->where('stage_id', $stage->id)
                        ->where('permission_type_id', $type->id)
                        ->first();
                    $this->permissionMatrix[$key] = $permission ? true : false;
                }
            }
        }
    }

    public function togglePermission(int $stageId, int $permissionTypeId): void
    {
        $key = "{$stageId}_{$permissionTypeId}";
        $currentValue = $this->permissionMatrix[$key] ?? false;

        if ($this->activeTab === 'users' && $this->selectedUserId) {
            if ($currentValue) {
                // إزالة الصلاحية
                UserStagePermission::where('user_id', $this->selectedUserId)
                    ->where('stage_id', $stageId)
                    ->where('permission_type_id', $permissionTypeId)
                    ->delete();
            } else {
                // إضافة الصلاحية
                UserStagePermission::create([
                    'user_id' => $this->selectedUserId,
                    'module_id' => $this->selectedModuleId,
                    'stage_id' => $stageId,
                    'permission_type_id' => $permissionTypeId,
                    'can_view_stage' => true,
                    'granted_by' => auth()->id(),
                ]);
            }
        } elseif ($this->activeTab === 'roles' && $this->selectedRoleId) {
            if ($currentValue) {
                // إزالة الصلاحية
                RoleStagePermission::where('role_id', $this->selectedRoleId)
                    ->where('stage_id', $stageId)
                    ->where('permission_type_id', $permissionTypeId)
                    ->delete();
            } else {
                // إضافة الصلاحية
                RoleStagePermission::create([
                    'role_id' => $this->selectedRoleId,
                    'module_id' => $this->selectedModuleId,
                    'stage_id' => $stageId,
                    'permission_type_id' => $permissionTypeId,
                    'can_view_stage' => true,
                ]);
            }
        }

        $this->permissionMatrix[$key] = !$currentValue;
    }

    public function toggleStageVisibility(int $stageId): void
    {
        if ($this->activeTab === 'users' && $this->selectedUserId) {
            $permissions = UserStagePermission::where('user_id', $this->selectedUserId)
                ->where('stage_id', $stageId)
                ->get();

            $currentVisibility = $permissions->first()?->can_view_stage ?? true;
            
            UserStagePermission::where('user_id', $this->selectedUserId)
                ->where('stage_id', $stageId)
                ->update(['can_view_stage' => !$currentVisibility]);
        } elseif ($this->activeTab === 'roles' && $this->selectedRoleId) {
            $permissions = RoleStagePermission::where('role_id', $this->selectedRoleId)
                ->where('stage_id', $stageId)
                ->get();

            $currentVisibility = $permissions->first()?->can_view_stage ?? true;
            
            RoleStagePermission::where('role_id', $this->selectedRoleId)
                ->where('stage_id', $stageId)
                ->update(['can_view_stage' => !$currentVisibility]);
        }
    }

    public function grantAllStagePermissions(int $stageId): void
    {
        $permissionTypes = $this->getPermissionTypes();

        foreach ($permissionTypes as $type) {
            $key = "{$stageId}_{$type->id}";
            
            if ($this->activeTab === 'users' && $this->selectedUserId) {
                UserStagePermission::updateOrCreate(
                    [
                        'user_id' => $this->selectedUserId,
                        'stage_id' => $stageId,
                        'permission_type_id' => $type->id,
                    ],
                    [
                        'module_id' => $this->selectedModuleId,
                        'can_view_stage' => true,
                        'granted_by' => auth()->id(),
                    ]
                );
            } elseif ($this->activeTab === 'roles' && $this->selectedRoleId) {
                RoleStagePermission::updateOrCreate(
                    [
                        'role_id' => $this->selectedRoleId,
                        'stage_id' => $stageId,
                        'permission_type_id' => $type->id,
                    ],
                    [
                        'module_id' => $this->selectedModuleId,
                        'can_view_stage' => true,
                    ]
                );
            }
            
            $this->permissionMatrix[$key] = true;
        }

        Notification::make()
            ->success()
            ->title('تم منح جميع الصلاحيات')
            ->send();
    }

    public function revokeAllStagePermissions(int $stageId): void
    {
        $permissionTypes = $this->getPermissionTypes();

        if ($this->activeTab === 'users' && $this->selectedUserId) {
            UserStagePermission::where('user_id', $this->selectedUserId)
                ->where('stage_id', $stageId)
                ->delete();
        } elseif ($this->activeTab === 'roles' && $this->selectedRoleId) {
            RoleStagePermission::where('role_id', $this->selectedRoleId)
                ->where('stage_id', $stageId)
                ->delete();
        }

        foreach ($permissionTypes as $type) {
            $key = "{$stageId}_{$type->id}";
            $this->permissionMatrix[$key] = false;
        }

        Notification::make()
            ->success()
            ->title('تم سحب جميع الصلاحيات')
            ->send();
    }

    public function getSelectedUserName(): string
    {
        if (!$this->selectedUserId) {
            return '';
        }
        return User::find($this->selectedUserId)?->name ?? '';
    }

    public function getSelectedRoleName(): string
    {
        if (!$this->selectedRoleId) {
            return '';
        }
        return Role::find($this->selectedRoleId)?->name ?? '';
    }

    public function getSelectedModuleName(): string
    {
        if (!$this->selectedModuleId) {
            return '';
        }
        $module = Module::find($this->selectedModuleId);
        return $module?->name_ar ?? '';
    }

    public function isStageVisible(int $stageId): bool
    {
        if ($this->activeTab === 'users' && $this->selectedUserId) {
            return UserStagePermission::where('user_id', $this->selectedUserId)
                ->where('stage_id', $stageId)
                ->where('can_view_stage', true)
                ->exists();
        } elseif ($this->activeTab === 'roles' && $this->selectedRoleId) {
            return RoleStagePermission::where('role_id', $this->selectedRoleId)
                ->where('stage_id', $stageId)
                ->where('can_view_stage', true)
                ->exists();
        }
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; // ستظهر من خلال AccessManagement
    }
}
