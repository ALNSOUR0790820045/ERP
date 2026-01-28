<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KpiResource\Pages;
use App\Models\Kpi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class KpiResource extends Resource
{
    protected static ?string $model = Kpi::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'التقارير ولوحات المتابعة';

    protected static ?string $modelLabel = 'مؤشر أداء';

    protected static ?string $pluralModelLabel = 'مؤشرات الأداء';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المؤشر')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('الرمز')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم (عربي)')
                            ->required(),
                        Forms\Components\TextInput::make('name_en')
                            ->label('الاسم (إنجليزي)'),
                        Forms\Components\Select::make('category')
                            ->label('التصنيف')
                            ->options([
                                'financial' => 'مالي',
                                'operational' => 'تشغيلي',
                                'quality' => 'الجودة',
                                'safety' => 'السلامة',
                                'hr' => 'الموارد البشرية',
                                'customer' => 'العملاء',
                                'project' => 'المشاريع',
                                'procurement' => 'المشتريات',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('unit')
                            ->label('الوحدة')
                            ->placeholder('%, JOD, days'),
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('العتبات والأهداف')
                    ->schema([
                        Forms\Components\TextInput::make('target_value')
                            ->label('القيمة المستهدفة')
                            ->numeric(),
                        Forms\Components\TextInput::make('warning_threshold')
                            ->label('عتبة التحذير')
                            ->numeric(),
                        Forms\Components\TextInput::make('critical_threshold')
                            ->label('عتبة الخطر')
                            ->numeric(),
                        Forms\Components\Select::make('comparison_type')
                            ->label('نوع المقارنة')
                            ->options([
                                'higher_better' => 'الأعلى أفضل',
                                'lower_better' => 'الأقل أفضل',
                                'target' => 'القيمة المستهدفة',
                            ])
                            ->default('higher_better'),
                    ])->columns(4),

                Forms\Components\Section::make('الإعدادات')
                    ->schema([
                        Forms\Components\Select::make('frequency')
                            ->label('التكرار')
                            ->options([
                                'daily' => 'يومي',
                                'weekly' => 'أسبوعي',
                                'monthly' => 'شهري',
                                'quarterly' => 'ربع سنوي',
                                'yearly' => 'سنوي',
                            ])
                            ->default('monthly'),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('ترتيب العرض')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('الرمز')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('التصنيف')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'financial' => 'مالي',
                        'operational' => 'تشغيلي',
                        'quality' => 'الجودة',
                        'safety' => 'السلامة',
                        'hr' => 'الموارد البشرية',
                        'customer' => 'العملاء',
                        'project' => 'المشاريع',
                        'procurement' => 'المشتريات',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('unit')
                    ->label('الوحدة'),
                Tables\Columns\TextColumn::make('target_value')
                    ->label('المستهدف')
                    ->numeric(),
                Tables\Columns\TextColumn::make('frequency')
                    ->label('التكرار')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'daily' => 'يومي',
                        'weekly' => 'أسبوعي',
                        'monthly' => 'شهري',
                        'quarterly' => 'ربع سنوي',
                        'yearly' => 'سنوي',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('التصنيف')
                    ->options([
                        'financial' => 'مالي',
                        'operational' => 'تشغيلي',
                        'quality' => 'الجودة',
                        'safety' => 'السلامة',
                        'hr' => 'الموارد البشرية',
                        'customer' => 'العملاء',
                        'project' => 'المشاريع',
                        'procurement' => 'المشتريات',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('نشط'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListKpis::route('/'),
            'create' => Pages\CreateKpi::route('/create'),
            'edit' => Pages\EditKpi::route('/{record}/edit'),
        ];
    }
}
