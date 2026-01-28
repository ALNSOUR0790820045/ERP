<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EarnedValueResource\Pages;
use App\Models\EarnedValue;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EarnedValueResource extends Resource
{
    protected static ?string $model = EarnedValue::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'إدارة المشاريع';
    protected static ?int $navigationSort = 25;

    public static function getModelLabel(): string
    {
        return 'تحليل القيمة المكتسبة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'تحليلات القيمة المكتسبة (EVM)';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات التحليل')->schema([
                Forms\Components\Select::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'name_ar')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\DatePicker::make('period_date')
                    ->label('تاريخ الفترة')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('period_type')
                    ->label('نوع الفترة')
                    ->options([
                        'weekly' => 'أسبوعي',
                        'monthly' => 'شهري',
                        'quarterly' => 'ربع سنوي',
                    ])
                    ->default('monthly'),
            ])->columns(3),

            Forms\Components\Section::make('الميزانية والقيم')->schema([
                Forms\Components\TextInput::make('bac')
                    ->label('الميزانية عند الإنجاز (BAC)')
                    ->numeric()
                    ->required()
                    ->helperText('Budget at Completion'),
                Forms\Components\TextInput::make('pv')
                    ->label('القيمة المخططة (PV)')
                    ->numeric()
                    ->required()
                    ->helperText('Planned Value'),
                Forms\Components\TextInput::make('ev')
                    ->label('القيمة المكتسبة (EV)')
                    ->numeric()
                    ->required()
                    ->helperText('Earned Value'),
                Forms\Components\TextInput::make('ac')
                    ->label('التكلفة الفعلية (AC)')
                    ->numeric()
                    ->required()
                    ->helperText('Actual Cost'),
            ])->columns(4),

            Forms\Components\Section::make('مؤشرات الانحراف')->schema([
                Forms\Components\TextInput::make('sv')
                    ->label('انحراف الجدول (SV)')
                    ->numeric()
                    ->helperText('Schedule Variance = EV - PV'),
                Forms\Components\TextInput::make('cv')
                    ->label('انحراف التكلفة (CV)')
                    ->numeric()
                    ->helperText('Cost Variance = EV - AC'),
                Forms\Components\TextInput::make('spi')
                    ->label('مؤشر أداء الجدول (SPI)')
                    ->numeric()
                    ->helperText('Schedule Performance Index = EV / PV'),
                Forms\Components\TextInput::make('cpi')
                    ->label('مؤشر أداء التكلفة (CPI)')
                    ->numeric()
                    ->helperText('Cost Performance Index = EV / AC'),
            ])->columns(4),

            Forms\Components\Section::make('التقديرات')->schema([
                Forms\Components\TextInput::make('eac')
                    ->label('التقدير عند الإنجاز (EAC)')
                    ->numeric()
                    ->helperText('Estimate at Completion'),
                Forms\Components\TextInput::make('etc')
                    ->label('التقدير للإنجاز (ETC)')
                    ->numeric()
                    ->helperText('Estimate to Complete'),
                Forms\Components\TextInput::make('vac')
                    ->label('الانحراف عند الإنجاز (VAC)')
                    ->numeric()
                    ->helperText('Variance at Completion'),
                Forms\Components\TextInput::make('tcpi')
                    ->label('مؤشر الأداء للإنجاز (TCPI)')
                    ->numeric()
                    ->helperText('To-Complete Performance Index'),
            ])->columns(4),

            Forms\Components\Section::make('نسب الإنجاز')->schema([
                Forms\Components\TextInput::make('percent_complete_planned')
                    ->label('نسبة الإنجاز المخططة %')
                    ->numeric()
                    ->suffix('%'),
                Forms\Components\TextInput::make('percent_complete_earned')
                    ->label('نسبة الإنجاز الفعلية %')
                    ->numeric()
                    ->suffix('%'),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'final' => 'نهائي',
                    ])
                    ->default('draft'),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.name_ar')
                    ->label('المشروع')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('period_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bac')
                    ->label('BAC')
                    ->money('JOD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ev')
                    ->label('EV')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('spi')
                    ->label('SPI')
                    ->numeric(decimalPlaces: 2)
                    ->color(fn ($state) => $state >= 1 ? 'success' : ($state >= 0.9 ? 'warning' : 'danger')),
                Tables\Columns\TextColumn::make('cpi')
                    ->label('CPI')
                    ->numeric(decimalPlaces: 2)
                    ->color(fn ($state) => $state >= 1 ? 'success' : ($state >= 0.9 ? 'warning' : 'danger')),
                Tables\Columns\TextColumn::make('percent_complete_earned')
                    ->label('الإنجاز %')
                    ->suffix('%'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'final',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'name_ar'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEarnedValues::route('/'),
            'create' => Pages\CreateEarnedValue::route('/create'),
            'edit' => Pages\EditEarnedValue::route('/{record}/edit'),
        ];
    }
}
