<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use App\Models\Permission;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'إعدادات النظام';
    protected static ?string $navigationLabel = 'الأدوار والصلاحيات';
    protected static ?string $modelLabel = 'دور';
    protected static ?string $pluralModelLabel = 'الأدوار والصلاحيات';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الدور')
                    ->icon('heroicon-o-identification')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('module')
                            ->label('الوحدة')
                            ->options(Role::getModules())
                            ->default('core')
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('code')
                            ->label('الرمز')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabled(fn ($record) => $record?->is_system)
                            ->maxLength(50),
                        Forms\Components\TextInput::make('name_ar')
                            ->label('الاسم بالعربي')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('name_en')
                            ->label('الاسم بالإنجليزي')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('level')
                            ->label('المستوى')
                            ->helperText('كلما زاد الرقم زادت الصلاحيات')
                            ->required()
                            ->numeric()
                            ->default(10)
                            ->minValue(1)
                            ->maxValue(100),
                        Forms\Components\Toggle::make('is_system')
                            ->label('دور نظام')
                            ->helperText('لا يمكن حذفه')
                            ->disabled(),
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('الصلاحيات')
                    ->icon('heroicon-o-key')
                    ->description('اختر الصلاحيات المتاحة لهذا الدور')
                    ->schema([
                        Forms\Components\Tabs::make('permissions_tabs')
                            ->tabs(self::getPermissionTabs())
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function getPermissionTabs(): array
    {
        $modules = Permission::getModules();
        $tabs = [];

        foreach ($modules as $moduleCode => $moduleName) {
            $permissions = Permission::where('module', $moduleCode)->get();
            
            if ($permissions->isEmpty()) {
                continue;
            }

            $tabs[] = Forms\Components\Tabs\Tab::make($moduleName)
                ->icon(self::getModuleIcon($moduleCode))
                ->schema([
                    Forms\Components\CheckboxList::make('permissions')
                        ->label('')
                        ->relationship('permissions', 'name_ar')
                        ->options(
                            $permissions->pluck('name_ar', 'id')
                        )
                        ->columns(3)
                        ->gridDirection('row')
                        ->bulkToggleable(),
                ]);
        }

        return $tabs;
    }

    protected static function getModuleIcon(string $module): string
    {
        return match($module) {
            'core' => 'heroicon-o-cog-6-tooth',
            'tenders' => 'heroicon-o-document-text',
            'contracts' => 'heroicon-o-document-check',
            'projects' => 'heroicon-o-building-office',
            'billing' => 'heroicon-o-currency-dollar',
            'suppliers' => 'heroicon-o-truck',
            'procurement' => 'heroicon-o-shopping-cart',
            'warehouse' => 'heroicon-o-cube',
            'manufacturing' => 'heroicon-o-wrench-screwdriver',
            'equipment' => 'heroicon-o-cog',
            'finance' => 'heroicon-o-banknotes',
            'hr' => 'heroicon-o-users',
            'crm' => 'heroicon-o-user-group',
            'quality' => 'heroicon-o-check-badge',
            'hse' => 'heroicon-o-shield-exclamation',
            'documents' => 'heroicon-o-folder',
            'reports' => 'heroicon-o-chart-bar',
            default => 'heroicon-o-squares-2x2',
        };
    }

    public static function table(Table $table): Table
    {
        return $table
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
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('الرمز')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                Tables\Columns\TextColumn::make('level')
                    ->label('المستوى')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state >= 90 => 'danger',
                        $state >= 70 => 'warning',
                        $state >= 50 => 'primary',
                        $state >= 30 => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('الصلاحيات')
                    ->counts('permissions')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('المستخدمين')
                    ->counts('users')
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('is_system')
                    ->label('نظام')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open'),
                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('level', 'desc')
            ->groups([
                Tables\Grouping\Group::make('module')
                    ->label('الوحدة')
                    ->getTitleFromRecordUsing(fn ($record) => Role::getModules()[$record->module] ?? 'النظام الأساسي'),
            ])
            ->defaultGroup('module')
            ->filters([
                Tables\Filters\SelectFilter::make('module')
                    ->label('الوحدة')
                    ->options(Role::getModules()),
                Tables\Filters\TernaryFilter::make('is_system')
                    ->label('نوع الدور')
                    ->placeholder('الكل')
                    ->trueLabel('أدوار النظام')
                    ->falseLabel('أدوار مخصصة'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => !$record->is_system),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            // منع حذف أدوار النظام
                            return $records->filter(fn ($r) => !$r->is_system);
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
