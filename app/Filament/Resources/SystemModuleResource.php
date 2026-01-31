<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemModuleResource\Pages;
use App\Models\SystemModule;
use App\Models\SystemScreen;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SystemModuleResource extends Resource
{
    protected static ?string $model = SystemModule::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationGroup = 'إدارة الصلاحيات والوصول';
    protected static ?string $navigationLabel = 'الوحدات والشاشات';
    protected static ?string $modelLabel = 'وحدة';
    protected static ?string $pluralModelLabel = 'الوحدات والشاشات';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الوحدة')
                    ->icon('heroicon-o-squares-2x2')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('الرمز')
                            ->placeholder('مثال: cleaning')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('رمز فريد بدون مسافات (بالإنجليزية)'),
                        Forms\Components\TextInput::make('name_ar')
                            ->label('الاسم بالعربي')
                            ->placeholder('مثال: إدارة النظافة')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('name_en')
                            ->label('الاسم بالإنجليزي')
                            ->placeholder('Example: Cleaning Management')
                            ->maxLength(100),
                        Forms\Components\Select::make('icon')
                            ->label('الأيقونة')
                            ->options([
                                'heroicon-o-home' => '🏠 الرئيسية',
                                'heroicon-o-users' => '👥 المستخدمين',
                                'heroicon-o-document-text' => '📄 المستندات',
                                'heroicon-o-banknotes' => '💵 المالية',
                                'heroicon-o-building-office' => '🏢 المباني',
                                'heroicon-o-truck' => '🚚 النقل',
                                'heroicon-o-cube' => '📦 المخزون',
                                'heroicon-o-wrench-screwdriver' => '🔧 الصيانة',
                                'heroicon-o-sparkles' => '✨ النظافة',
                                'heroicon-o-shield-check' => '🛡️ الأمان',
                                'heroicon-o-chart-bar' => '📊 التقارير',
                                'heroicon-o-cog-6-tooth' => '⚙️ الإعدادات',
                                'heroicon-o-calendar' => '📅 الجدولة',
                                'heroicon-o-clipboard-document-list' => '📋 القوائم',
                                'heroicon-o-shopping-cart' => '🛒 المشتريات',
                                'heroicon-o-academic-cap' => '🎓 التدريب',
                                'heroicon-o-beaker' => '🧪 المختبرات',
                                'heroicon-o-bolt' => '⚡ الطاقة',
                            ])
                            ->searchable()
                            ->default('heroicon-o-squares-2x2'),
                        Forms\Components\Select::make('color')
                            ->label('اللون')
                            ->options([
                                'gray' => '⬜ رمادي',
                                'red' => '🟥 أحمر',
                                'orange' => '🟧 برتقالي',
                                'yellow' => '🟨 أصفر',
                                'green' => '🟩 أخضر',
                                'blue' => '🟦 أزرق',
                                'indigo' => '🟪 نيلي',
                                'purple' => '💜 بنفسجي',
                                'pink' => '💗 وردي',
                                'cyan' => '🔵 سماوي',
                                'teal' => '🌊 فيروزي',
                            ])
                            ->default('gray'),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('الترتيب')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشطة')
                            ->default(true)
                            ->helperText('الوحدة النشطة تظهر في نظام الصلاحيات'),
                    ]),

                Forms\Components\Section::make('شاشات الوحدة')
                    ->icon('heroicon-o-computer-desktop')
                    ->description('أضف الشاشات التي تتبع هذه الوحدة')
                    ->schema([
                        Forms\Components\Repeater::make('screens')
                            ->label('')
                            ->relationship('screens')
                            ->schema([
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('code')
                                            ->label('رمز الشاشة')
                                            ->placeholder('مثال: cleaning_schedules')
                                            ->required()
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('name_ar')
                                            ->label('الاسم بالعربي')
                                            ->placeholder('مثال: جداول النظافة')
                                            ->required()
                                            ->maxLength(150),
                                        Forms\Components\TextInput::make('name_en')
                                            ->label('الاسم بالإنجليزي')
                                            ->placeholder('Cleaning Schedules')
                                            ->maxLength(150),
                                        Forms\Components\TextInput::make('sort_order')
                                            ->label('الترتيب')
                                            ->numeric()
                                            ->default(0),
                                    ]),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('resource_class')
                                            ->label('كلاس Filament Resource (اختياري)')
                                            ->placeholder('App\\Filament\\Resources\\CleaningScheduleResource')
                                            ->maxLength(255),
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('نشطة')
                                            ->default(true),
                                    ]),
                            ])
                            ->columns(1)
                            ->addActionLabel('إضافة شاشة جديدة')
                            ->reorderable()
                            ->collapsible()
                            ->defaultItems(0)
                            ->itemLabel(fn (array $state): ?string => $state['name_ar'] ?? 'شاشة جديدة'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\IconColumn::make('icon')
                    ->label('')
                    ->icon(fn ($state) => $state ?? 'heroicon-o-squares-2x2')
                    ->color(fn ($record) => $record->color ?? 'gray'),
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('الوحدة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('code')
                    ->label('الرمز')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('screens_count')
                    ->label('الشاشات')
                    ->counts('screens')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('roles_count')
                    ->label('الأدوار المرتبطة')
                    ->counts('roles')
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشطة')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة'),
            ])
            ->actions([
                Tables\Actions\Action::make('manage_screens')
                    ->label('الشاشات')
                    ->icon('heroicon-o-computer-desktop')
                    ->color('success')
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record]) . '#شاشات-الوحدة'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        // حذف الشاشات المرتبطة
                        $record->screens()->delete();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSystemModules::route('/'),
            'create' => Pages\CreateSystemModule::route('/create'),
            'edit' => Pages\EditSystemModule::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
}
