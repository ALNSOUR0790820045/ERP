<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EquipmentResource\Pages;
use App\Models\Equipment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EquipmentResource extends Resource
{
    protected static ?string $model = Equipment::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'المعدات';
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'معدة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'المعدات';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Tabs')->tabs([
                Forms\Components\Tabs\Tab::make('البيانات الأساسية')->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('كود المعدة')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50),
                    Forms\Components\TextInput::make('name_ar')
                        ->label('الاسم بالعربية')
                        ->required(),
                    Forms\Components\TextInput::make('name_en')
                        ->label('الاسم بالإنجليزية'),
                    Forms\Components\Select::make('category_id')
                        ->label('التصنيف')
                        ->relationship('category', 'name_ar'),
                    Forms\Components\Textarea::make('description')
                        ->label('الوصف')
                        ->rows(2)
                        ->columnSpan(2),
                ])->columns(2),

                Forms\Components\Tabs\Tab::make('المواصفات الفنية')->schema([
                    Forms\Components\TextInput::make('brand')->label('الماركة'),
                    Forms\Components\TextInput::make('model')->label('الموديل'),
                    Forms\Components\TextInput::make('serial_number')->label('الرقم التسلسلي'),
                    Forms\Components\TextInput::make('year_manufactured')
                        ->label('سنة الصنع')
                        ->numeric(),
                    Forms\Components\TextInput::make('fuel_type')->label('نوع الوقود'),
                    Forms\Components\TextInput::make('fuel_consumption')
                        ->label('استهلاك الوقود')
                        ->numeric(),
                    Forms\Components\TextInput::make('capacity')
                        ->label('السعة')
                        ->numeric(),
                    Forms\Components\TextInput::make('capacity_unit')->label('وحدة السعة'),
                ])->columns(2),

                Forms\Components\Tabs\Tab::make('الملكية والتكاليف')->schema([
                    Forms\Components\Select::make('ownership_type')
                        ->label('نوع الملكية')
                        ->options([
                            'owned' => 'مملوكة',
                            'rented' => 'مستأجرة',
                            'leased' => 'تأجير تمويلي',
                        ])
                        ->default('owned'),
                    Forms\Components\DatePicker::make('purchase_date')->label('تاريخ الشراء'),
                    Forms\Components\TextInput::make('purchase_price')
                        ->label('سعر الشراء')
                        ->numeric(),
                    Forms\Components\TextInput::make('current_value')
                        ->label('القيمة الحالية')
                        ->numeric(),
                    Forms\Components\TextInput::make('hourly_rate')
                        ->label('معدل الساعة')
                        ->numeric(),
                    Forms\Components\TextInput::make('daily_rate')
                        ->label('معدل اليوم')
                        ->numeric(),
                    Forms\Components\TextInput::make('monthly_rate')
                        ->label('معدل الشهر')
                        ->numeric(),
                    Forms\Components\TextInput::make('depreciation_rate')
                        ->label('نسبة الإهلاك %')
                        ->numeric(),
                ])->columns(2),

                Forms\Components\Tabs\Tab::make('الحالة والتعيين')->schema([
                    Forms\Components\Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'available' => 'متاحة',
                            'assigned' => 'معينة لمشروع',
                            'maintenance' => 'صيانة',
                            'breakdown' => 'عاطلة',
                            'disposed' => 'مستبعدة',
                        ])
                        ->default('available'),
                    Forms\Components\Select::make('current_project_id')
                        ->label('المشروع الحالي')
                        ->relationship('currentProject', 'name_ar'),
                    Forms\Components\TextInput::make('odometer')
                        ->label('عداد المسافة')
                        ->numeric(),
                    Forms\Components\TextInput::make('hour_meter')
                        ->label('عداد الساعات')
                        ->numeric(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true),
                ])->columns(2),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('الكود')->searchable(),
                Tables\Columns\TextColumn::make('name_ar')->label('الاسم')->searchable(),
                Tables\Columns\TextColumn::make('category.name_ar')->label('التصنيف'),
                Tables\Columns\TextColumn::make('brand')->label('الماركة'),
                Tables\Columns\TextColumn::make('status')->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'assigned' => 'info',
                        'maintenance' => 'warning',
                        'breakdown' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('currentProject.name_ar')->label('المشروع'),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'available' => 'متاحة',
                        'assigned' => 'معينة',
                        'maintenance' => 'صيانة',
                        'breakdown' => 'عاطلة',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEquipment::route('/'),
            'create' => Pages\CreateEquipment::route('/create'),
            'edit' => Pages\EditEquipment::route('/{record}/edit'),
        ];
    }
}
