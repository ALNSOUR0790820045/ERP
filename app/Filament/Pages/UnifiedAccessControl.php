<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Module;
use App\Models\ModuleStage;
use App\Models\Permission;
use App\Models\PermissionTemplate;
use App\Models\PermissionType;
use App\Models\Role;
use App\Models\RoleStagePermission;
use App\Models\SystemModule;
use App\Models\SystemScreen;
use App\Models\Team;
use App\Models\User;
use App\Models\UserStagePermission;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowStep;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Url;

class UnifiedAccessControl extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'مركز الصلاحيات';
    protected static ?string $title = 'مركز إدارة الصلاحيات والوصول';
    protected static ?string $navigationGroup = 'إعدادات النظام';
    protected static ?int $navigationSort = 0;
    protected static string $view = 'filament.pages.unified-access-control';

    #[Url]
    public string $mainTab = 'dashboard';
    
    #[Url]
    public ?int $selectedUserId = null;
    
    #[Url]
    public ?int $selectedRoleId = null;

    public string $permissionView = 'matrix'; // matrix, list, tree
    public ?int $selectedModuleId = null;
    public array $permissionMatrix = [];

    public function mount(): void
    {
        // تحديد أول وحدة
        $firstModule = Module::first();
        if ($firstModule) {
            $this->selectedModuleId = $firstModule->id;
        }
    }

    // =============== الإحصائيات ===============
    public function getStats(): array
    {
        return [
            'users' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'inactive' => User::where('is_active', false)->count(),
                'new_this_month' => User::whereMonth('created_at', now()->month)->count(),
            ],
            'roles' => [
                'total' => Role::count(),
                'system' => Role::where('type', 'system')->count(),
                'job' => Role::where('type', 'job')->count(),
                'tender' => Role::where('type', 'tender')->count(),
            ],
            'modules' => [
                'total' => SystemModule::count(),
                'screens' => SystemScreen::count(),
            ],
            'workflows' => [
                'total' => WorkflowDefinition::count(),
                'active' => WorkflowDefinition::where('is_active', true)->count(),
                'steps' => WorkflowStep::count(),
            ],
            'stage_permissions' => [
                'user_permissions' => UserStagePermission::count(),
                'role_permissions' => RoleStagePermission::count(),
            ],
        ];
    }

    // =============== لوحة المعلومات ===============
    public function getDashboardData(): array
    {
        return [
            'recent_users' => User::with('role')
                ->latest()
                ->take(5)
                ->get(),
            'active_workflows' => WorkflowDefinition::where('is_active', true)
                ->withCount('steps')
                ->take(5)
                ->get(),
            'roles_summary' => Role::withCount(['users'])
                ->orderByDesc('users_count')
                ->take(5)
                ->get(),
            'modules_usage' => SystemModule::withCount('roles')
                ->orderByDesc('roles_count')
                ->get(),
        ];
    }

    // =============== الجداول ===============
    public function table(Table $table): Table
    {
        return match($this->mainTab) {
            'users' => $this->getUsersTable($table),
            'roles' => $this->getRolesTable($table),
            'workflows' => $this->getWorkflowsTable($table),
            'templates' => $this->getTemplatesTable($table),
            default => $this->getUsersTable($table),
        };
    }

    protected function getUsersTable(Table $table): Table
    {
        return $table
            ->query(User::query()->with(['role', 'branch', 'roles']))
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=random'),
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->email),
                Tables\Columns\TextColumn::make('roles.name_ar')
                    ->label('الأدوار')
                    ->badge()
                    ->separator(',')
                    ->colors([
                        'danger' => fn ($state, $record) => $record->roles->contains('code', 'super_admin'),
                        'warning' => fn ($state, $record) => $record->roles->contains('type', 'job'),
                        'success' => fn ($state, $record) => $record->roles->contains('type', 'tender'),
                    ])
                    ->limitList(3)
                    ->expandableLimitedList(),
                Tables\Columns\TextColumn::make('branch.name_ar')
                    ->label('الفرع')
                    ->placeholder('بدون فرع'),
                Tables\Columns\TextColumn::make('stage_permissions_count')
                    ->label('صلاحيات المراحل')
                    ->getStateUsing(fn ($record) => UserStagePermission::where('user_id', $record->id)->count())
                    ->badge()
                    ->color('purple'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('آخر دخول')
                    ->since()
                    ->placeholder('لم يسجل بعد'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_id')
                    ->label('الدور الأساسي')
                    ->relationship('role', 'name_ar'),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('الفرع')
                    ->relationship('branch', 'name_ar'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('نشط'),
                Tables\Filters\Filter::make('has_stage_permissions')
                    ->label('لديه صلاحيات مراحل')
                    ->query(fn (Builder $query) => $query->whereHas('userStagePermissions')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view_permissions')
                        ->label('عرض الصلاحيات')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->action(fn ($record) => $this->selectUserForPermissions($record->id)),
                    Tables\Actions\Action::make('edit')
                        ->label('تعديل')
                        ->icon('heroicon-o-pencil')
                        ->url(fn ($record) => route('filament.admin.resources.users.edit', $record)),
                    Tables\Actions\Action::make('manage_roles')
                        ->label('إدارة الأدوار')
                        ->icon('heroicon-o-shield-check')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('job_roles')
                                ->label('الأدوار الوظيفية')
                                ->multiple()
                                ->options(Role::where('type', 'job')->pluck('name_ar', 'id'))
                                ->default(fn ($record) => $record->roles()->where('type', 'job')->pluck('id')->toArray()),
                            Forms\Components\Select::make('tender_roles')
                                ->label('أدوار العطاءات')
                                ->multiple()
                                ->options(Role::where('type', 'tender')->pluck('name_ar', 'id'))
                                ->default(fn ($record) => $record->roles()->where('type', 'tender')->pluck('id')->toArray()),
                        ])
                        ->action(function ($record, array $data) {
                            $allRoles = array_merge($data['job_roles'] ?? [], $data['tender_roles'] ?? []);
                            $record->syncRoles($allRoles);
                            Notification::make()
                                ->success()
                                ->title('تم تحديث الأدوار')
                                ->send();
                        }),
                    Tables\Actions\Action::make('quick_permissions')
                        ->label('صلاحيات سريعة')
                        ->icon('heroicon-o-bolt')
                        ->color('purple')
                        ->form([
                            Forms\Components\Select::make('template_id')
                                ->label('تطبيق قالب صلاحيات')
                                ->options(PermissionTemplate::where('is_active', true)->pluck('name_ar', 'id'))
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $template = PermissionTemplate::find($data['template_id']);
                            if ($template) {
                                $template->applyToUser($record->id, auth()->id());
                                Notification::make()
                                    ->success()
                                    ->title('تم تطبيق القالب')
                                    ->send();
                            }
                        }),
                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn ($record) => $record->is_active ? 'تعطيل' : 'تفعيل')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['is_active' => !$record->is_active]);
                        }),
                ])->dropdown(false)->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_apply_template')
                    ->label('تطبيق قالب للمحدد')
                    ->icon('heroicon-o-document-duplicate')
                    ->form([
                        Forms\Components\Select::make('template_id')
                            ->label('القالب')
                            ->options(PermissionTemplate::where('is_active', true)->pluck('name_ar', 'id'))
                            ->required(),
                    ])
                    ->action(function ($records, array $data) {
                        $template = PermissionTemplate::find($data['template_id']);
                        if ($template) {
                            foreach ($records as $record) {
                                $template->applyToUser($record->id, auth()->id());
                            }
                            Notification::make()
                                ->success()
                                ->title('تم تطبيق القالب على ' . $records->count() . ' مستخدم')
                                ->send();
                        }
                    }),
            ]);
    }

    protected function getRolesTable(Table $table): Table
    {
        return $table
            ->query(Role::query()->withCount(['users']))
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('المسمى')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon(fn ($record) => $record->icon ?? 'heroicon-o-shield-check'),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'system' => '🛡️ نظام',
                        'job' => '💼 وظيفي',
                        'tender' => '📋 عطاءات',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'system' => 'danger',
                        'job' => 'warning',
                        'tender' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('systemModules.name_ar')
                    ->label('الوحدات')
                    ->badge()
                    ->color('info')
                    ->separator(', ')
                    ->limitList(3)
                    ->expandableLimitedList(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('المستخدمين')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('stage_permissions_count')
                    ->label('صلاحيات المراحل')
                    ->getStateUsing(fn ($record) => RoleStagePermission::where('role_id', $record->id)->count())
                    ->badge()
                    ->color('purple'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->groups([
                Tables\Grouping\Group::make('type')
                    ->label('النوع')
                    ->collapsible(),
            ])
            ->defaultGroup('type')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'system' => 'نظام',
                        'job' => 'وظيفي',
                        'tender' => 'عطاءات',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view_permissions')
                        ->label('عرض الصلاحيات')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->action(fn ($record) => $this->selectRoleForPermissions($record->id)),
                    Tables\Actions\Action::make('edit')
                        ->label('تعديل')
                        ->icon('heroicon-o-pencil')
                        ->url(fn ($record) => route('filament.admin.resources.roles.edit', $record)),
                    Tables\Actions\Action::make('clone')
                        ->label('نسخ')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->form([
                            Forms\Components\TextInput::make('name_ar')
                                ->label('اسم الدور الجديد')
                                ->required()
                                ->default(fn ($record) => 'نسخة من ' . $record->name_ar),
                            Forms\Components\TextInput::make('code')
                                ->label('الرمز')
                                ->required()
                                ->default(fn ($record) => $record->code . '_copy'),
                        ])
                        ->action(function ($record, array $data) {
                            $newRole = $record->replicate(['id', 'is_system', 'created_at', 'updated_at']);
                            $newRole->name_ar = $data['name_ar'];
                            $newRole->code = $data['code'];
                            $newRole->is_system = false;
                            $newRole->save();
                            
                            // نسخ الوحدات
                            $newRole->systemModules()->sync($record->systemModules->pluck('id'));
                            
                            // نسخ صلاحيات المراحل
                            foreach ($record->stagePermissions ?? [] as $perm) {
                                RoleStagePermission::create([
                                    'role_id' => $newRole->id,
                                    'module_id' => $perm->module_id,
                                    'stage_id' => $perm->stage_id,
                                    'permission_type_id' => $perm->permission_type_id,
                                    'is_granted' => $perm->is_granted,
                                ]);
                            }
                            
                            Notification::make()
                                ->success()
                                ->title('تم نسخ الدور')
                                ->send();
                        }),
                ])->dropdown(false)->iconButton(),
            ]);
    }

    protected function getWorkflowsTable(Table $table): Table
    {
        return $table
            ->query(WorkflowDefinition::query()->withCount('steps'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('entity_type')
                    ->label('نوع الكيان')
                    ->formatStateUsing(fn ($state) => WorkflowDefinition::ENTITY_TYPES[$state] ?? class_basename($state))
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('steps_count')
                    ->label('الخطوات')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعّال')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('design')
                    ->label('تصميم')
                    ->icon('heroicon-o-paint-brush')
                    ->color('primary')
                    ->url(fn ($record) => route('filament.admin.pages.workflow-designer', ['workflow' => $record->id])),
                Tables\Actions\Action::make('edit')
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => route('filament.admin.resources.workflow-definitions.edit', $record)),
            ]);
    }

    protected function getTemplatesTable(Table $table): Table
    {
        return $table
            ->query(PermissionTemplate::query())
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('الاسم')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('module.name_ar')
                    ->label('الوحدة')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(50),
                Tables\Columns\TextColumn::make('usage_count')
                    ->label('مرات الاستخدام')
                    ->getStateUsing(fn ($record) => $record->usage_count ?? 0)
                    ->badge()
                    ->color('success'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => route('filament.admin.resources.permission-templates.edit', $record)),
            ]);
    }

    // =============== إدارة الصلاحيات ===============
    public function selectUserForPermissions(int $userId): void
    {
        $this->selectedUserId = $userId;
        $this->selectedRoleId = null;
        $this->mainTab = 'permissions';
        $this->loadPermissionMatrix();
    }

    public function selectRoleForPermissions(int $roleId): void
    {
        $this->selectedRoleId = $roleId;
        $this->selectedUserId = null;
        $this->mainTab = 'permissions';
        $this->loadPermissionMatrix();
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

    public function selectModule(int $moduleId): void
    {
        $this->selectedModuleId = $moduleId;
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
                
                if ($this->selectedUserId) {
                    $permission = UserStagePermission::where('user_id', $this->selectedUserId)
                        ->where('stage_id', $stage->id)
                        ->where('permission_type_id', $type->id)
                        ->first();
                } elseif ($this->selectedRoleId) {
                    $permission = RoleStagePermission::where('role_id', $this->selectedRoleId)
                        ->where('stage_id', $stage->id)
                        ->where('permission_type_id', $type->id)
                        ->first();
                } else {
                    $permission = null;
                }

                $this->permissionMatrix[$key] = $permission?->can_view_stage ?? false;
            }
        }
    }

    public function togglePermission(int $stageId, int $typeId): void
    {
        $key = "{$stageId}_{$typeId}";
        $newValue = !($this->permissionMatrix[$key] ?? false);

        if ($this->selectedUserId) {
            UserStagePermission::updateOrCreate(
                [
                    'user_id' => $this->selectedUserId,
                    'module_id' => $this->selectedModuleId,
                    'stage_id' => $stageId,
                    'permission_type_id' => $typeId,
                ],
                [
                    'can_view_stage' => $newValue,
                    'granted_by' => auth()->id(),
                ]
            );
        } elseif ($this->selectedRoleId) {
            RoleStagePermission::updateOrCreate(
                [
                    'role_id' => $this->selectedRoleId,
                    'module_id' => $this->selectedModuleId,
                    'stage_id' => $stageId,
                    'permission_type_id' => $typeId,
                ],
                [
                    'can_view_stage' => $newValue,
                ]
            );
        }

        $this->permissionMatrix[$key] = $newValue;
    }

    public function grantAllForStage(int $stageId): void
    {
        $permissionTypes = $this->getPermissionTypes();
        
        foreach ($permissionTypes as $type) {
            $key = "{$stageId}_{$type->id}";
            
            if ($this->selectedUserId) {
                UserStagePermission::updateOrCreate(
                    [
                        'user_id' => $this->selectedUserId,
                        'module_id' => $this->selectedModuleId,
                        'stage_id' => $stageId,
                        'permission_type_id' => $type->id,
                    ],
                    [
                        'can_view_stage' => true,
                        'granted_by' => auth()->id(),
                    ]
                );
            } elseif ($this->selectedRoleId) {
                RoleStagePermission::updateOrCreate(
                    [
                        'role_id' => $this->selectedRoleId,
                        'module_id' => $this->selectedModuleId,
                        'stage_id' => $stageId,
                        'permission_type_id' => $type->id,
                    ],
                    ['can_view_stage' => true]
                );
            }
            
            $this->permissionMatrix[$key] = true;
        }

        Notification::make()
            ->success()
            ->title('تم منح جميع الصلاحيات للمرحلة')
            ->send();
    }

    public function revokeAllForStage(int $stageId): void
    {
        if ($this->selectedUserId) {
            UserStagePermission::where('user_id', $this->selectedUserId)
                ->where('module_id', $this->selectedModuleId)
                ->where('stage_id', $stageId)
                ->delete();
        } elseif ($this->selectedRoleId) {
            RoleStagePermission::where('role_id', $this->selectedRoleId)
                ->where('module_id', $this->selectedModuleId)
                ->where('stage_id', $stageId)
                ->delete();
        }

        $permissionTypes = $this->getPermissionTypes();
        foreach ($permissionTypes as $type) {
            $key = "{$stageId}_{$type->id}";
            $this->permissionMatrix[$key] = false;
        }

        Notification::make()
            ->success()
            ->title('تم سحب جميع صلاحيات المرحلة')
            ->send();
    }

    public function getSelectedEntityName(): string
    {
        if ($this->selectedUserId) {
            return User::find($this->selectedUserId)?->name ?? 'مستخدم';
        }
        if ($this->selectedRoleId) {
            return Role::find($this->selectedRoleId)?->name_ar ?? 'دور';
        }
        return '';
    }

    // =============== التنقل ===============
    public function setMainTab(string $tab): void
    {
        $this->mainTab = $tab;
        if ($tab !== 'permissions') {
            $this->selectedUserId = null;
            $this->selectedRoleId = null;
        }
        $this->resetTable();
    }

    public function backToList(): void
    {
        if ($this->selectedUserId) {
            $this->setMainTab('users');
        } else {
            $this->setMainTab('roles');
        }
    }

    // =============== الإجراءات ===============
    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_user')
                ->label('مستخدم جديد')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->url(route('filament.admin.resources.users.create'))
                ->visible(fn () => $this->mainTab === 'users'),

            Action::make('create_role')
                ->label('دور جديد')
                ->icon('heroicon-o-shield-plus')
                ->color('warning')
                ->url(route('filament.admin.resources.roles.create'))
                ->visible(fn () => $this->mainTab === 'roles'),

            Action::make('create_workflow')
                ->label('سير عمل جديد')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->url(route('filament.admin.resources.workflow-definitions.create'))
                ->visible(fn () => $this->mainTab === 'workflows'),

            Action::make('back_to_list')
                ->label('العودة للقائمة')
                ->icon('heroicon-o-arrow-right')
                ->color('gray')
                ->action(fn () => $this->backToList())
                ->visible(fn () => $this->mainTab === 'permissions'),

            Action::make('save_permissions')
                ->label('حفظ الصلاحيات')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action(function () {
                    Notification::make()
                        ->success()
                        ->title('تم حفظ الصلاحيات')
                        ->send();
                })
                ->visible(fn () => $this->mainTab === 'permissions' && ($this->selectedUserId || $this->selectedRoleId)),

            Action::make('apply_template')
                ->label('تطبيق قالب')
                ->icon('heroicon-o-document-duplicate')
                ->color('purple')
                ->form([
                    Forms\Components\Select::make('template_id')
                        ->label('اختر القالب')
                        ->options(PermissionTemplate::where('is_active', true)->pluck('name_ar', 'id'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    $template = PermissionTemplate::find($data['template_id']);
                    if ($template) {
                        if ($this->selectedUserId) {
                            $template->applyToUser($this->selectedUserId, auth()->id());
                        } elseif ($this->selectedRoleId) {
                            $template->applyToRole($this->selectedRoleId);
                        }
                        $this->loadPermissionMatrix();
                        Notification::make()
                            ->success()
                            ->title('تم تطبيق القالب')
                            ->send();
                    }
                })
                ->visible(fn () => $this->mainTab === 'permissions' && ($this->selectedUserId || $this->selectedRoleId)),

            Action::make('clear_all')
                ->label('مسح الكل')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('تأكيد المسح')
                ->modalDescription('هل أنت متأكد من مسح جميع صلاحيات هذه الوحدة؟')
                ->action(function () {
                    if ($this->selectedUserId) {
                        UserStagePermission::where('user_id', $this->selectedUserId)
                            ->where('module_id', $this->selectedModuleId)
                            ->delete();
                    } elseif ($this->selectedRoleId) {
                        RoleStagePermission::where('role_id', $this->selectedRoleId)
                            ->where('module_id', $this->selectedModuleId)
                            ->delete();
                    }
                    $this->loadPermissionMatrix();
                    Notification::make()
                        ->success()
                        ->title('تم مسح الصلاحيات')
                        ->send();
                })
                ->visible(fn () => $this->mainTab === 'permissions' && ($this->selectedUserId || $this->selectedRoleId)),
        ];
    }
}
