<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemModule;
use App\Models\SystemScreen;
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
    protected static ?string $navigationGroup = 'إدارة الصلاحيات والوصول';
    protected static ?string $navigationLabel = 'الأدوار الوظيفية';
    protected static ?string $modelLabel = 'دور وظيفي';
    protected static ?string $pluralModelLabel = 'الأدوار الوظيفية';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // الخطوة 1: معلومات الدور الأساسية
                Forms\Components\Section::make('معلومات الدور الوظيفي')
                    ->icon('heroicon-o-identification')
                    ->description('حدد المعلومات الأساسية للدور الوظيفي')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('name_ar')
                            ->label('المسمى الوظيفي بالعربي')
                            ->placeholder('مثال: مدير مالي، محاسب، مهندس موقع')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('name_en')
                            ->label('المسمى الوظيفي بالإنجليزي')
                            ->placeholder('Example: Financial Manager')
                            ->maxLength(100),
                        Forms\Components\Select::make('type')
                            ->label('نوع الدور')
                            ->options([
                                'system' => '🛡️ دور نظام',
                                'job' => '💼 دور وظيفي',
                                'tender' => '📋 دور عطاءات',
                            ])
                            ->default('job')
                            ->required()
                            ->live()
                            ->helperText('نوع الدور يحدد استخدامه'),
                        Forms\Components\TextInput::make('code')
                            ->label('الرمز')
                            ->placeholder('مثال: financial_manager')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabled(fn ($record) => $record?->is_system)
                            ->maxLength(50)
                            ->helperText('رمز فريد للدور (بدون مسافات)'),
                        Forms\Components\TextInput::make('level')
                            ->label('مستوى الصلاحية')
                            ->helperText('1-100: كلما زاد الرقم زادت الصلاحيات')
                            ->required()
                            ->numeric()
                            ->default(10)
                            ->minValue(1)
                            ->maxValue(100),
                        Forms\Components\Select::make('icon')
                            ->label('الأيقونة')
                            ->options([
                                'heroicon-o-shield-check' => '🛡️ درع',
                                'heroicon-o-banknotes' => '💵 نقود',
                                'heroicon-o-calculator' => '🔢 آلة حاسبة',
                                'heroicon-o-briefcase' => '💼 حقيبة',
                                'heroicon-o-user-group' => '👥 مجموعة',
                                'heroicon-o-cube' => '📦 صندوق',
                                'heroicon-o-shopping-cart' => '🛒 عربة',
                                'heroicon-o-clipboard-document-list' => '📋 قائمة',
                                'heroicon-o-wrench-screwdriver' => '🔧 أدوات',
                                'heroicon-o-document-text' => '📄 مستند',
                            ])
                            ->searchable(),
                        Forms\Components\Select::make('color')
                            ->label('اللون')
                            ->options([
                                'primary' => '🔵 أزرق',
                                'success' => '🟢 أخضر',
                                'warning' => '🟡 أصفر',
                                'danger' => '🔴 أحمر',
                                'info' => '🔷 سماوي',
                                'gray' => '⚫ رمادي',
                            ]),
                        Forms\Components\Toggle::make('is_active')
                            ->label('دور نشط')
                            ->default(true)
                            ->inline(false),
                        Forms\Components\Textarea::make('description')
                            ->label('وصف الدور')
                            ->placeholder('وصف مختصر لمهام ومسؤوليات هذا الدور')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                // الخطوة 2: الوحدات المسموح بها
                Forms\Components\Section::make('الوحدات المسموح الوصول إليها')
                    ->icon('heroicon-o-squares-2x2')
                    ->description('اختر الوحدات التي يمكن لهذا الدور الوصول إليها')
                    ->schema([
                        Forms\Components\CheckboxList::make('systemModules')
                            ->label('')
                            ->relationship('systemModules', 'name_ar')
                            ->options(
                                SystemModule::where('is_active', true)
                                    ->orderBy('sort_order')
                                    ->pluck('name_ar', 'id')
                            )
                            ->descriptions(
                                SystemModule::where('is_active', true)
                                    ->pluck('description', 'id')
                                    ->toArray()
                            )
                            ->columns(3)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->live()
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('selected_modules', $state)),
                    ]),

                // الخطوة 3: الشاشات والصلاحيات التفصيلية
                Forms\Components\Section::make('الشاشات والصلاحيات التفصيلية')
                    ->icon('heroicon-o-computer-desktop')
                    ->description('حدد الشاشات المسموح بها وصلاحيات كل شاشة')
                    ->schema(function (Forms\Get $get) {
                        $selectedModuleIds = $get('systemModules') ?? [];
                        
                        if (empty($selectedModuleIds)) {
                            return [
                                Forms\Components\Placeholder::make('no_modules')
                                    ->content('يرجى اختيار الوحدات أولاً من القسم السابق')
                                    ->columnSpanFull(),
                            ];
                        }

                        $modules = SystemModule::whereIn('id', $selectedModuleIds)
                            ->where('is_active', true)
                            ->with(['screens' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
                            ->orderBy('sort_order')
                            ->get();

                        $tabs = [];
                        foreach ($modules as $module) {
                            if ($module->screens->isEmpty()) {
                                continue;
                            }

                            $screenCheckboxes = [];
                            foreach ($module->screens as $screen) {
                                $screenCheckboxes[] = Forms\Components\Grid::make(7)
                                    ->schema([
                                        Forms\Components\Placeholder::make("screen_name_{$screen->id}")
                                            ->label('')
                                            ->content($screen->name_ar)
                                            ->columnSpan(1),
                                        Forms\Components\Toggle::make("screens.{$screen->id}.can_view")
                                            ->label('عرض')
                                            ->inline(false)
                                            ->columnSpan(1),
                                        Forms\Components\Toggle::make("screens.{$screen->id}.can_create")
                                            ->label('إنشاء')
                                            ->inline(false)
                                            ->columnSpan(1),
                                        Forms\Components\Toggle::make("screens.{$screen->id}.can_edit")
                                            ->label('تعديل')
                                            ->inline(false)
                                            ->columnSpan(1),
                                        Forms\Components\Toggle::make("screens.{$screen->id}.can_delete")
                                            ->label('حذف')
                                            ->inline(false)
                                            ->columnSpan(1),
                                        Forms\Components\Toggle::make("screens.{$screen->id}.can_export")
                                            ->label('تصدير')
                                            ->inline(false)
                                            ->columnSpan(1),
                                        Forms\Components\Toggle::make("screens.{$screen->id}.can_print")
                                            ->label('طباعة')
                                            ->inline(false)
                                            ->columnSpan(1),
                                    ]);
                            }

                            $tabs[] = Forms\Components\Tabs\Tab::make($module->name_ar)
                                ->icon($module->icon ?? 'heroicon-o-squares-2x2')
                                ->schema([
                                    Forms\Components\Fieldset::make('صلاحيات الشاشات')
                                        ->schema($screenCheckboxes),
                                ]);
                        }

                        if (empty($tabs)) {
                            return [
                                Forms\Components\Placeholder::make('no_screens')
                                    ->content('لا توجد شاشات معرفة للوحدات المختارة')
                                    ->columnSpanFull(),
                            ];
                        }

                        return [
                            Forms\Components\Tabs::make('module_screens')
                                ->tabs($tabs)
                                ->columnSpanFull(),
                        ];
                    })
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('المسمى الوظيفي')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon(fn ($record) => $record->icon ?? 'heroicon-o-user'),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'system' => 'نظام',
                        'job' => 'وظيفي',
                        'tender' => 'عطاءات',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'system' => 'danger',
                        'job' => 'success',
                        'tender' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('الرمز')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('systemModules.name_ar')
                    ->label('الوحدات')
                    ->badge()
                    ->color('success')
                    ->separator(', ')
                    ->limitList(2)
                    ->expandableLimitedList(),
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
                Tables\Columns\TextColumn::make('users_count')
                    ->label('المستخدمين')
                    ->counts('users')
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('level', 'desc')
            ->groups([
                Tables\Grouping\Group::make('type')
                    ->label('النوع')
                    ->getTitleFromRecordUsing(fn ($record) => match($record->type) {
                        'system' => '🛡️ أدوار النظام',
                        'job' => '💼 الأدوار الوظيفية',
                        'tender' => '📋 أدوار العطاءات',
                        default => 'أخرى',
                    })
                    ->collapsible(),
            ])
            ->defaultGroup('type')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'system' => '🛡️ نظام',
                        'job' => '💼 وظيفي',
                        'tender' => '📋 عطاءات',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => !$record->is_system && $record->type !== 'system'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            return $records->filter(fn ($r) => !$r->is_system && $r->type !== 'system');
                        }),
                ]),
            ])
            ->emptyStateHeading('لا توجد أدوار')
            ->emptyStateDescription('ابدأ بإضافة دور جديد')
            ->emptyStateIcon('heroicon-o-shield-check');
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
