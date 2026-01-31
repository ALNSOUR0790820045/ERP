<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Module;
use App\Models\ModuleStage;
use App\Models\Permission;
use App\Models\PermissionTemplate;
use App\Models\PermissionType;
use App\Models\Role;
use App\Models\SystemModule;
use App\Models\SystemScreen;
use App\Models\Team;
use App\Models\User;
use App\Models\UserStagePermission;
use App\Models\WorkflowDefinition;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Url;

class AccessManagement extends Page implements HasTable, HasForms, HasInfolists
{
    use InteractsWithTable;
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'إدارة الصلاحيات والوصول';
    protected static ?string $title = 'إدارة الصلاحيات والوصول';
    protected static ?string $navigationGroup = 'إعدادات النظام';
    protected static ?int $navigationSort = 1;
    protected static bool $shouldRegisterNavigation = false; // إخفاء من القائمة - استخدم UnifiedAccessControl
    protected static string $view = 'filament.pages.access-management';

    #[Url]
    public string $activeTab = 'users';

    // إحصائيات
    public function getStats(): array
    {
        return [
            'users' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'inactive' => User::where('is_active', false)->count(),
            ],
            'roles' => [
                'total' => Role::count(),
                'system' => Role::where('is_system', true)->count(),
                'custom' => Role::where('is_system', false)->count(),
            ],
            'teams' => [
                'total' => Team::count(),
                'active' => Team::where('is_active', true)->count(),
            ],
            'permissions' => [
                'total' => Permission::count(),
                'modules' => Permission::distinct('module')->count('module'),
            ],
            'workflows' => [
                'total' => WorkflowDefinition::count(),
                'active' => WorkflowDefinition::where('is_active', true)->count(),
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return match($this->activeTab) {
            'users' => $this->getUsersTable($table),
            'roles' => $this->getRolesTable($table),
            'teams' => $this->getTeamsTable($table),
            'permissions' => $this->getPermissionsTable($table),
            'workflows' => $this->getWorkflowsTable($table),
            default => $this->getUsersTable($table),
        };
    }

    protected function getUsersTable(Table $table): Table
    {
        return $table
            ->query(User::query()->with(['role', 'branch']))
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=random'),
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role.name_ar')
                    ->label('الدور')
                    ->badge()
                    ->color(fn ($record) => match($record->role?->code) {
                        'super_admin' => 'danger',
                        'company_admin' => 'warning',
                        'tender_manager' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('branch.name_ar')
                    ->label('الفرع'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('آخر دخول')
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_id')
                    ->label('الدور')
                    ->relationship('role', 'name_ar'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('نشط'),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => route('filament.admin.resources.users.edit', $record)),
                Tables\Actions\Action::make('manage_stage_permissions')
                    ->label('صلاحيات المراحل')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('purple')
                    ->url(fn ($record) => route('filament.admin.pages.stage-permission-manager') . '?user=' . $record->id),
                Tables\Actions\Action::make('apply_template')
                    ->label('تطبيق قالب')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('template_id')
                            ->label('اختر القالب')
                            ->options(PermissionTemplate::where('is_active', true)->pluck('name_ar', 'id'))
                            ->required()
                            ->helperText('سيتم إضافة صلاحيات القالب للمستخدم'),
                    ])
                    ->action(function ($record, array $data) {
                        $template = PermissionTemplate::find($data['template_id']);
                        if ($template) {
                            $template->applyToUser($record->id, auth()->id());
                            Notification::make()
                                ->success()
                                ->title('تم تطبيق القالب')
                                ->body("تم تطبيق قالب {$template->name_ar} على {$record->name}")
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('assign_role')
                    ->label('تغيير الدور')
                    ->icon('heroicon-o-shield-check')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('role_id')
                            ->label('الدور الجديد')
                            ->options(Role::pluck('name_ar', 'id'))
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update(['role_id' => $data['role_id']]);
                        Notification::make()
                            ->title('تم تغيير الدور بنجاح')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('add_to_team')
                    ->label('إضافة لفريق')
                    ->icon('heroicon-o-user-group')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('team_id')
                            ->label('الفريق')
                            ->options(Team::where('is_active', true)->pluck('name_ar', 'id'))
                            ->required(),
                        Forms\Components\Select::make('role_in_team')
                            ->label('الدور في الفريق')
                            ->options([
                                'leader' => 'قائد',
                                'member' => 'عضو',
                                'viewer' => 'مشاهد',
                            ])
                            ->default('member'),
                    ])
                    ->action(function ($record, array $data) {
                        $team = Team::find($data['team_id']);
                        $team->addMember($record, $data['role_in_team']);
                        Notification::make()
                            ->title('تمت الإضافة للفريق بنجاح')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->is_active ? 'تعطيل' : 'تفعيل')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);
                        Notification::make()
                            ->success()
                            ->title($record->is_active ? 'تم تفعيل المستخدم' : 'تم تعطيل المستخدم')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_assign_role')
                    ->label('تغيير الدور للمحدد')
                    ->icon('heroicon-o-shield-check')
                    ->form([
                        Forms\Components\Select::make('role_id')
                            ->label('الدور')
                            ->options(Role::pluck('name_ar', 'id'))
                            ->required(),
                    ])
                    ->action(function ($records, array $data) {
                        $records->each->update(['role_id' => $data['role_id']]);
                        Notification::make()
                            ->title('تم تغيير الأدوار بنجاح')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected function getRolesTable(Table $table): Table
    {
        return $table
            ->query(Role::query()->withCount(['users']))
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('المسمى الوظيفي')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('code')
                    ->label('الرمز')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('systemModules.name_ar')
                    ->label('الوحدات')
                    ->badge()
                    ->color('success')
                    ->separator(', ')
                    ->limitList(3)
                    ->expandableLimitedList(),
                Tables\Columns\TextColumn::make('level')
                    ->label('المستوى')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state >= 90 => 'danger',
                        $state >= 70 => 'warning',
                        $state >= 50 => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('المستخدمين')
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('is_system')
                    ->label('نظام')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_system')
                    ->label('دور نظام'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.roles.view', $record)),
                Tables\Actions\Action::make('edit')
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => route('filament.admin.resources.roles.edit', $record)),
            ]);
    }

    protected function getTeamsTable(Table $table): Table
    {
        return $table
            ->query(Team::query()->withCount(['teamMembers as members_count' => fn($q) => $q->where('is_active', true)]))
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('الرمز')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'general' => 'عام',
                        'tender' => 'عطاءات',
                        'project' => 'مشروع',
                        'pricing' => 'تسعير',
                        'technical' => 'فني',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'tender' => 'success',
                        'project' => 'info',
                        'pricing' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('leader.name')
                    ->label('القائد'),
                Tables\Columns\TextColumn::make('members_count')
                    ->label('الأعضاء')
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.teams.view', $record)),
                Tables\Actions\Action::make('edit')
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => route('filament.admin.resources.teams.edit', $record)),
                Tables\Actions\Action::make('add_members')
                    ->label('إضافة أعضاء')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('users')
                            ->label('المستخدمين')
                            ->multiple()
                            ->options(User::where('is_active', true)->pluck('name', 'id'))
                            ->searchable(),
                    ])
                    ->action(function ($record, array $data) {
                        foreach ($data['users'] as $userId) {
                            $user = User::find($userId);
                            if ($user) {
                                $record->addMember($user);
                            }
                        }
                        Notification::make()
                            ->title('تمت إضافة الأعضاء بنجاح')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected function getPermissionsTable(Table $table): Table
    {
        return $table
            ->query(Permission::query()->withCount('roles'))
            ->columns([
                Tables\Columns\TextColumn::make('module')
                    ->label('الوحدة')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'tenders' => 'success',
                        'contracts' => 'info',
                        'projects' => 'warning',
                        'hr' => 'primary',
                        'finance' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('code')
                    ->label('الرمز')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('الوصف')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('roles_count')
                    ->label('الأدوار')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module')
                    ->label('الوحدة')
                    ->options(
                        Permission::distinct()->pluck('module', 'module')->toArray()
                    ),
            ])
            ->groups([
                Tables\Grouping\Group::make('module')
                    ->label('الوحدة'),
            ])
            ->defaultGroup('module');
    }

    protected function getWorkflowsTable(Table $table): Table
    {
        return $table
            ->query(WorkflowDefinition::query()->withCount('steps'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('الرمز')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('entity_type')
                    ->label('نوع الكيان')
                    ->formatStateUsing(fn ($state) => WorkflowDefinition::ENTITY_TYPES[$state] ?? class_basename($state))
                    ->badge(),
                Tables\Columns\TextColumn::make('steps_count')
                    ->label('الخطوات')
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعّال')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => route('filament.admin.resources.workflow-definitions.edit', $record)),
                Tables\Actions\Action::make('manage_steps')
                    ->label('إدارة الخطوات')
                    ->icon('heroicon-o-queue-list')
                    ->color('info')
                    ->url(fn ($record) => route('filament.admin.pages.workflow-designer', ['workflow' => $record->id])),
            ]);
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_user')
                ->label('مستخدم جديد')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->modalWidth('4xl')
                ->modalHeading('إضافة مستخدم جديد')
                ->modalDescription('أدخل بيانات المستخدم واختر صلاحياته')
                ->form([
                    Forms\Components\Wizard::make([
                        Forms\Components\Wizard\Step::make('البيانات الأساسية')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('الاسم الكامل')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('email')
                                        ->label('البريد الإلكتروني')
                                        ->email()
                                        ->required()
                                        ->unique(User::class, 'email')
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('phone')
                                        ->label('رقم الهاتف')
                                        ->tel()
                                        ->maxLength(20),
                                    Forms\Components\TextInput::make('password')
                                        ->label('كلمة المرور')
                                        ->password()
                                        ->required()
                                        ->minLength(8)
                                        ->revealable(),
                                ]),
                            ]),
                        Forms\Components\Wizard\Step::make('الدور والفرع')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\Select::make('role_id')
                                        ->label('الدور الوظيفي')
                                        ->options(Role::orderBy('name_ar')->pluck('name_ar', 'id'))
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->helperText('اختر الدور الذي يحدد صلاحيات المستخدم الأساسية'),
                                    Forms\Components\Select::make('branch_id')
                                        ->label('الفرع')
                                        ->options(Branch::orderBy('name_ar')->pluck('name_ar', 'id'))
                                        ->searchable()
                                        ->preload(),
                                    Forms\Components\Toggle::make('is_active')
                                        ->label('نشط')
                                        ->default(true)
                                        ->helperText('المستخدم النشط يمكنه تسجيل الدخول'),
                                    Forms\Components\Toggle::make('must_change_password')
                                        ->label('يجب تغيير كلمة المرور')
                                        ->default(true)
                                        ->helperText('سيُطلب من المستخدم تغيير كلمة المرور عند أول تسجيل دخول'),
                                ]),
                            ]),
                        Forms\Components\Wizard\Step::make('صلاحيات المراحل')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Forms\Components\Placeholder::make('permissions_info')
                                    ->content('يمكنك منح صلاحيات إضافية على مراحل محددة (اختياري)'),
                                Forms\Components\Select::make('permission_template_id')
                                    ->label('تطبيق قالب صلاحيات سريع')
                                    ->options(PermissionTemplate::where('is_active', true)->pluck('name_ar', 'id'))
                                    ->placeholder('اختر قالب (اختياري)')
                                    ->helperText('القوالب توفر صلاحيات جاهزة مثل: سكرتير عطاءات، مدير عطاءات')
                                    ->live(),
                                Forms\Components\Select::make('team_id')
                                    ->label('إضافة لفريق عمل')
                                    ->options(Team::where('is_active', true)->pluck('name_ar', 'id'))
                                    ->placeholder('اختر فريق (اختياري)')
                                    ->helperText('يمكنك إضافة المستخدم لفريق عمل'),
                            ]),
                    ])->skippable(),
                ])
                ->action(function (array $data): void {
                    DB::beginTransaction();
                    try {
                        // إنشاء المستخدم
                        $user = User::create([
                            'name' => $data['name'],
                            'email' => $data['email'],
                            'phone' => $data['phone'] ?? null,
                            'password' => Hash::make($data['password']),
                            'role_id' => $data['role_id'],
                            'branch_id' => $data['branch_id'] ?? null,
                            'is_active' => $data['is_active'] ?? true,
                            'must_change_password' => $data['must_change_password'] ?? true,
                        ]);

                        // تطبيق قالب الصلاحيات إن وجد
                        if (!empty($data['permission_template_id'])) {
                            $template = PermissionTemplate::find($data['permission_template_id']);
                            if ($template) {
                                $template->applyToUser($user->id, auth()->id());
                            }
                        }

                        // إضافة للفريق إن وجد
                        if (!empty($data['team_id'])) {
                            $team = Team::find($data['team_id']);
                            if ($team) {
                                $team->addMember($user, 'member');
                            }
                        }

                        DB::commit();

                        Notification::make()
                            ->success()
                            ->title('تم إنشاء المستخدم بنجاح')
                            ->body("تم إنشاء حساب {$user->name} وتعيين صلاحياته")
                            ->send();

                    } catch (\Exception $e) {
                        DB::rollBack();
                        Notification::make()
                            ->danger()
                            ->title('خطأ في إنشاء المستخدم')
                            ->body($e->getMessage())
                            ->send();
                    }
                })
                ->visible(fn () => $this->activeTab === 'users'),

            Action::make('quick_add_user')
                ->label('إضافة سريعة')
                ->icon('heroicon-o-bolt')
                ->color('gray')
                ->modalWidth('lg')
                ->modalHeading('إضافة مستخدم سريعة')
                ->form([
                    Forms\Components\TextInput::make('name')
                        ->label('الاسم')
                        ->required(),
                    Forms\Components\TextInput::make('email')
                        ->label('البريد')
                        ->email()
                        ->required()
                        ->unique(User::class, 'email'),
                    Forms\Components\Select::make('role_id')
                        ->label('الدور')
                        ->options(Role::orderBy('name_ar')->pluck('name_ar', 'id'))
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $defaultPassword = 'password123';
                    
                    User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => Hash::make($defaultPassword),
                        'role_id' => $data['role_id'],
                        'is_active' => true,
                        'must_change_password' => true,
                    ]);

                    Notification::make()
                        ->success()
                        ->title('تم إنشاء المستخدم')
                        ->body("كلمة المرور الافتراضية: {$defaultPassword}")
                        ->persistent()
                        ->send();
                })
                ->visible(fn () => $this->activeTab === 'users'),

            Action::make('create_role')
                ->label('دور جديد')
                ->icon('heroicon-o-plus-circle')
                ->color('warning')
                ->url(route('filament.admin.resources.roles.create'))
                ->visible(fn () => $this->activeTab === 'roles'),

            Action::make('create_team')
                ->label('فريق جديد')
                ->icon('heroicon-o-user-group')
                ->color('info')
                ->url(route('filament.admin.resources.teams.create'))
                ->visible(fn () => $this->activeTab === 'teams'),

            Action::make('create_workflow')
                ->label('سير عمل جديد')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->url(route('filament.admin.resources.workflow-definitions.create'))
                ->visible(fn () => $this->activeTab === 'workflows'),
        ];
    }
}
