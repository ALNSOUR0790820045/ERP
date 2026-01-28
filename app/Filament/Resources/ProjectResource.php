<?php

namespace App\Filament\Resources;

use App\Enums\ProjectStatus;
use App\Enums\ProjectPriority;
use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    
    protected static ?string $navigationGroup = 'المشاريع';
    
    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'مشروع';
    
    protected static ?string $pluralModelLabel = 'المشاريع';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('project_tabs')
                    ->tabs([
                        // Tab 1: البيانات الأساسية
                        Forms\Components\Tabs\Tab::make('البيانات الأساسية')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Section::make('معلومات المشروع')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('project_number')
                                            ->label('رقم المشروع')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(50),
                                            
                                        Forms\Components\TextInput::make('code')
                                            ->label('رمز المشروع')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(20),
                                            
                                        Forms\Components\Select::make('project_type_id')
                                            ->label('نوع المشروع')
                                            ->relationship('projectType', 'name_ar')
                                            ->searchable()
                                            ->preload(),
                                            
                                        Forms\Components\TextInput::make('name_ar')
                                            ->label('اسم المشروع (عربي)')
                                            ->required()
                                            ->maxLength(500)
                                            ->columnSpan(2),
                                            
                                        Forms\Components\Select::make('priority')
                                            ->label('الأولوية')
                                            ->options(ProjectPriority::class)
                                            ->default(ProjectPriority::MEDIUM),
                                            
                                        Forms\Components\TextInput::make('name_en')
                                            ->label('اسم المشروع (إنجليزي)')
                                            ->maxLength(500)
                                            ->columnSpan(2),
                                            
                                        Forms\Components\Select::make('status')
                                            ->label('الحالة')
                                            ->options(ProjectStatus::class)
                                            ->default(ProjectStatus::PLANNING),
                                            
                                        Forms\Components\Textarea::make('description')
                                            ->label('وصف المشروع')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                                    
                                Forms\Components\Section::make('الربط')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\Select::make('contract_id')
                                            ->label('العقد')
                                            ->relationship('contract', 'contract_number')
                                            ->searchable()
                                            ->preload(),
                                            
                                        Forms\Components\Select::make('company_id')
                                            ->label('الشركة')
                                            ->relationship('company', 'name_ar')
                                            ->searchable()
                                            ->preload(),
                                            
                                        Forms\Components\Select::make('branch_id')
                                            ->label('الفرع')
                                            ->relationship('branch', 'name_ar')
                                            ->searchable()
                                            ->preload(),
                                    ]),
                            ]),
                            
                        // Tab 2: العميل والموقع
                        Forms\Components\Tabs\Tab::make('العميل والموقع')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Forms\Components\Section::make('العميل')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Select::make('customer_id')
                                            ->label('العميل')
                                            ->relationship('customer', 'name_ar')
                                            ->searchable()
                                            ->preload(),
                                            
                                        Forms\Components\Select::make('consultant_id')
                                            ->label('الاستشاري')
                                            ->relationship('consultant', 'name_ar')
                                            ->searchable()
                                            ->preload(),
                                    ]),
                                    
                                Forms\Components\Section::make('الموقع')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\Select::make('country_id')
                                            ->label('الدولة')
                                            ->relationship('country', 'name_ar')
                                            ->searchable()
                                            ->preload()
                                            ->live(),
                                            
                                        Forms\Components\Select::make('city_id')
                                            ->label('المدينة')
                                            ->relationship('city', 'name_ar', function ($query, $get) {
                                                if ($get('country_id')) {
                                                    $query->where('country_id', $get('country_id'));
                                                }
                                            })
                                            ->searchable()
                                            ->preload(),
                                            
                                        Forms\Components\Textarea::make('address')
                                            ->label('العنوان التفصيلي')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                            
                                        Forms\Components\TextInput::make('latitude')
                                            ->label('خط العرض')
                                            ->numeric(),
                                            
                                        Forms\Components\TextInput::make('longitude')
                                            ->label('خط الطول')
                                            ->numeric(),
                                            
                                        Forms\Components\TextInput::make('site_area')
                                            ->label('مساحة الموقع (م²)')
                                            ->numeric(),
                                            
                                        Forms\Components\TextInput::make('building_area')
                                            ->label('مساحة البناء (م²)')
                                            ->numeric(),
                                    ]),
                            ]),
                            
                        // Tab 3: التواريخ والمدة
                        Forms\Components\Tabs\Tab::make('التواريخ والمدة')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Forms\Components\Section::make('التواريخ المخططة')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\DatePicker::make('planned_start_date')
                                            ->label('تاريخ البدء المخطط'),
                                            
                                        Forms\Components\DatePicker::make('planned_end_date')
                                            ->label('تاريخ الانتهاء المخطط'),
                                            
                                        Forms\Components\TextInput::make('duration_days')
                                            ->label('المدة (أيام)')
                                            ->numeric(),
                                    ]),
                                    
                                Forms\Components\Section::make('التواريخ الفعلية')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\DatePicker::make('actual_start_date')
                                            ->label('تاريخ البدء الفعلي'),
                                            
                                        Forms\Components\DatePicker::make('actual_end_date')
                                            ->label('تاريخ الانتهاء الفعلي'),
                                    ]),
                                    
                                Forms\Components\Section::make('إعدادات العمل')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('working_days_per_week')
                                            ->label('أيام العمل الأسبوعية')
                                            ->numeric()
                                            ->default(6)
                                            ->minValue(1)
                                            ->maxValue(7),
                                            
                                        Forms\Components\TextInput::make('working_hours_per_day')
                                            ->label('ساعات العمل اليومية')
                                            ->numeric()
                                            ->default(8)
                                            ->minValue(1)
                                            ->maxValue(24),
                                    ]),
                            ]),
                            
                        // Tab 4: القيم المالية
                        Forms\Components\Tabs\Tab::make('القيم المالية')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\Section::make('الميزانية')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('contract_value')
                                            ->label('قيمة العقد')
                                            ->numeric()
                                            ->default(0),
                                            
                                        Forms\Components\TextInput::make('budget')
                                            ->label('الميزانية')
                                            ->numeric()
                                            ->default(0),
                                            
                                        Forms\Components\Select::make('currency_id')
                                            ->label('العملة')
                                            ->relationship('currency', 'code')
                                            ->searchable()
                                            ->preload(),
                                            
                                        Forms\Components\TextInput::make('actual_cost')
                                            ->label('التكلفة الفعلية')
                                            ->numeric()
                                            ->default(0)
                                            ->disabled(),
                                    ]),
                            ]),
                            
                        // Tab 5: فريق المشروع
                        Forms\Components\Tabs\Tab::make('فريق المشروع')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Forms\Components\Section::make('الفريق')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Select::make('project_manager_id')
                                            ->label('مدير المشروع')
                                            ->relationship('projectManager', 'name')
                                            ->searchable()
                                            ->preload(),
                                            
                                        Forms\Components\Select::make('site_engineer_id')
                                            ->label('مهندس الموقع')
                                            ->relationship('siteEngineer', 'name')
                                            ->searchable()
                                            ->preload(),
                                            
                                        Forms\Components\Select::make('safety_officer_id')
                                            ->label('مسؤول السلامة')
                                            ->relationship('safetyOfficer', 'name')
                                            ->searchable()
                                            ->preload(),
                                            
                                        Forms\Components\Select::make('quality_officer_id')
                                            ->label('مسؤول الجودة')
                                            ->relationship('qualityOfficer', 'name')
                                            ->searchable()
                                            ->preload(),
                                            
                                        Forms\Components\Select::make('accountant_id')
                                            ->label('محاسب المشروع')
                                            ->relationship('accountant', 'name')
                                            ->searchable()
                                            ->preload(),
                                    ]),
                            ]),
                            
                        // Tab 6: التقدم
                        Forms\Components\Tabs\Tab::make('التقدم')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Forms\Components\Section::make('نسب الإنجاز')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('planned_progress')
                                            ->label('التقدم المخطط %')
                                            ->numeric()
                                            ->default(0)
                                            ->suffix('%'),
                                            
                                        Forms\Components\TextInput::make('actual_progress')
                                            ->label('التقدم الفعلي %')
                                            ->numeric()
                                            ->default(0)
                                            ->suffix('%'),
                                    ]),
                                    
                                Forms\Components\Section::make('مؤشرات EVM')
                                    ->columns(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('pv')
                                            ->label('PV - القيمة المخططة')
                                            ->numeric()
                                            ->disabled(),
                                            
                                        Forms\Components\TextInput::make('ev')
                                            ->label('EV - القيمة المكتسبة')
                                            ->numeric()
                                            ->disabled(),
                                            
                                        Forms\Components\TextInput::make('ac')
                                            ->label('AC - التكلفة الفعلية')
                                            ->numeric()
                                            ->disabled(),
                                            
                                        Forms\Components\TextInput::make('spi')
                                            ->label('SPI - مؤشر الجدول')
                                            ->numeric()
                                            ->disabled(),
                                            
                                        Forms\Components\TextInput::make('cpi')
                                            ->label('CPI - مؤشر التكلفة')
                                            ->numeric()
                                            ->disabled(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project_number')
                    ->label('رقم المشروع')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('code')
                    ->label('الرمز')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('اسم المشروع')
                    ->searchable()
                    ->limit(40)
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('customer.name_ar')
                    ->label('العميل')
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('contract_value')
                    ->label('قيمة العقد')
                    ->money('JOD')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('planned_start_date')
                    ->label('تاريخ البدء')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('planned_end_date')
                    ->label('تاريخ الانتهاء')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('actual_progress')
                    ->label('الإنجاز %')
                    ->suffix('%')
                    ->color(fn ($state) => $state >= 100 ? 'success' : ($state >= 50 ? 'warning' : 'danger')),
                    
                Tables\Columns\TextColumn::make('priority')
                    ->label('الأولوية')
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(ProjectStatus::class),
                    
                Tables\Filters\SelectFilter::make('priority')
                    ->label('الأولوية')
                    ->options(ProjectPriority::class),
                    
                Tables\Filters\SelectFilter::make('project_type_id')
                    ->label('نوع المشروع')
                    ->relationship('projectType', 'name_ar'),
                    
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('العميل')
                    ->relationship('customer', 'name_ar'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\WbsItemsRelationManager::class,
            RelationManagers\DailyReportsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view' => Pages\ViewProject::route('/{record}'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
