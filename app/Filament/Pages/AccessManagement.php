<?php

namespace App\Filament\Pages;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
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
    protected static ?int $navigationSort = 0;
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
            ->query(Role::query()->withCount(['permissions', 'users']))
            ->columns([
                Tables\Columns\TextColumn::make('module')
                    ->label('الوحدة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Role::getModules()[$state] ?? 'النظام الأساسي')
                    ->color(fn ($state) => match($state) {
                        'tenders' => 'success',
                        'contracts' => 'warning',
                        'projects' => 'info',
                        'finance' => 'danger',
                        'hr' => 'purple',
                        'inventory' => 'orange',
                        'procurement' => 'cyan',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('الرمز')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('level')
                    ->label('المستوى')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state >= 90 => 'danger',
                        $state >= 70 => 'warning',
                        $state >= 50 => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('الصلاحيات')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('المستخدمين')
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('is_system')
                    ->label('نظام')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module')
                    ->label('الوحدة')
                    ->options(Role::getModules()),
                Tables\Filters\TernaryFilter::make('is_system')
                    ->label('دور نظام'),
            ])
            ->groups([
                Tables\Grouping\Group::make('module')
                    ->label('الوحدة')
                    ->getTitleFromRecordUsing(fn ($record) => Role::getModules()[$record->module] ?? 'النظام الأساسي'),
            ])
            ->defaultGroup('module')
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.roles.view', $record)),
                Tables\Actions\Action::make('edit')
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => route('filament.admin.resources.roles.edit', $record)),
                Tables\Actions\Action::make('manage_permissions')
                    ->label('إدارة الصلاحيات')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->form([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('الصلاحيات')
                            ->options(Permission::pluck('name_ar', 'id'))
                            ->columns(3)
                            ->default(fn ($record) => $record->permissions->pluck('id')->toArray()),
                    ])
                    ->action(function ($record, array $data) {
                        $record->permissions()->sync($data['permissions']);
                        Notification::make()
                            ->title('تم تحديث الصلاحيات بنجاح')
                            ->success()
                            ->send();
                    }),
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
                ->url(route('filament.admin.resources.users.create'))
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
