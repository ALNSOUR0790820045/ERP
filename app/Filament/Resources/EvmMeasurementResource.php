<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvmMeasurementResource\Pages;
use App\Models\EvmMeasurement;
use App\Models\Project;
use App\Models\ProjectBaseline;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;

class EvmMeasurementResource extends Resource
{
    protected static ?string $model = EvmMeasurement::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'إدارة المشاريع';

    protected static ?string $navigationLabel = 'قياسات EVM';

    protected static ?string $modelLabel = 'قياس EVM';

    protected static ?string $pluralModelLabel = 'قياسات القيمة المكتسبة';

    protected static ?int $navigationSort = 25;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('البيانات الأساسية')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('project_id')
                                    ->label('المشروع')
                                    ->relationship('project', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn ($set) => $set('baseline_id', null)),

                                Forms\Components\Select::make('baseline_id')
                                    ->label('خط الأساس')
                                    ->options(function ($get) {
                                        $projectId = $get('project_id');
                                        if (!$projectId) return [];
                                        return ProjectBaseline::where('project_id', $projectId)
                                            ->where('status', 'active')
                                            ->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable(),

                                Forms\Components\TextInput::make('measurement_number')
                                    ->label('رقم القياس')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('measurement_date')
                                    ->label('تاريخ القياس')
                                    ->required()
                                    ->default(now()),

                                Forms\Components\DatePicker::make('data_date')
                                    ->label('تاريخ البيانات')
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
                            ]),
                    ]),

                Forms\Components\Section::make('القيم الأساسية')
                    ->description('أدخل القيم المخططة والمكتسبة والفعلية')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('budget_at_completion')
                                    ->label('الميزانية عند الإنجاز (BAC)')
                                    ->numeric()
                                    ->required()
                                    ->prefix('JOD'),

                                Forms\Components\TextInput::make('planned_value')
                                    ->label('القيمة المخططة (PV)')
                                    ->numeric()
                                    ->required()
                                    ->prefix('JOD'),

                                Forms\Components\TextInput::make('earned_value')
                                    ->label('القيمة المكتسبة (EV)')
                                    ->numeric()
                                    ->required()
                                    ->prefix('JOD'),

                                Forms\Components\TextInput::make('actual_cost')
                                    ->label('التكلفة الفعلية (AC)')
                                    ->numeric()
                                    ->required()
                                    ->prefix('JOD'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('physical_progress')
                                    ->label('نسبة الإنجاز الفعلي %')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%'),

                                Forms\Components\TextInput::make('planned_progress')
                                    ->label('نسبة الإنجاز المخطط %')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%'),
                            ]),
                    ]),

                Forms\Components\Section::make('التحليل والملاحظات')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Textarea::make('analysis_notes')
                            ->label('ملاحظات التحليل')
                            ->rows(3),

                        Forms\Components\Textarea::make('corrective_actions')
                            ->label('الإجراءات التصحيحية')
                            ->rows(3),

                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'draft' => 'مسودة',
                                'submitted' => 'مقدم',
                                'approved' => 'معتمد',
                                'rejected' => 'مرفوض',
                            ])
                            ->default('draft'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('measurement_number')
                    ->label('رقم القياس')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('المشروع')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('measurement_date')
                    ->label('تاريخ القياس')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('schedule_performance_index')
                    ->label('SPI')
                    ->numeric(2)
                    ->badge()
                    ->color(fn ($record) => $record->spi_color),

                Tables\Columns\TextColumn::make('cost_performance_index')
                    ->label('CPI')
                    ->numeric(2)
                    ->badge()
                    ->color(fn ($record) => $record->cpi_color),

                Tables\Columns\TextColumn::make('physical_progress')
                    ->label('الإنجاز %')
                    ->numeric(2)
                    ->suffix('%'),

                Tables\Columns\TextColumn::make('overall_status')
                    ->label('الحالة العامة')
                    ->badge()
                    ->color(fn ($record) => $record->overall_status_color)
                    ->formatStateUsing(fn ($state) => match($state) {
                        'green' => 'جيد',
                        'yellow' => 'تحذير',
                        'red' => 'حرج',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('حالة القياس')
                    ->badge()
                    ->color(fn ($record) => $record->status_color),
            ])
            ->defaultSort('measurement_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'name'),

                Tables\Filters\SelectFilter::make('overall_status')
                    ->label('الحالة العامة')
                    ->options([
                        'green' => 'جيد',
                        'yellow' => 'تحذير',
                        'red' => 'حرج',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('حالة القياس')
                    ->options([
                        'draft' => 'مسودة',
                        'submitted' => 'مقدم',
                        'approved' => 'معتمد',
                        'rejected' => 'مرفوض',
                    ]),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('اعتماد')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'submitted')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات القياس')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('measurement_number')
                                    ->label('رقم القياس'),
                                Infolists\Components\TextEntry::make('project.name')
                                    ->label('المشروع'),
                                Infolists\Components\TextEntry::make('measurement_date')
                                    ->label('تاريخ القياس')
                                    ->date(),
                            ]),
                    ]),

                Infolists\Components\Section::make('مؤشرات الأداء')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('schedule_performance_index')
                                    ->label('SPI')
                                    ->weight(FontWeight::Bold)
                                    ->badge()
                                    ->color(fn ($record) => $record->spi_color),

                                Infolists\Components\TextEntry::make('cost_performance_index')
                                    ->label('CPI')
                                    ->weight(FontWeight::Bold)
                                    ->badge()
                                    ->color(fn ($record) => $record->cpi_color),

                                Infolists\Components\TextEntry::make('critical_ratio')
                                    ->label('النسبة الحرجة'),

                                Infolists\Components\TextEntry::make('to_complete_performance_index')
                                    ->label('TCPI'),
                            ]),
                    ]),

                Infolists\Components\Section::make('القيم والفروقات')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('planned_value')
                                    ->label('القيمة المخططة (PV)')
                                    ->money('JOD'),
                                Infolists\Components\TextEntry::make('earned_value')
                                    ->label('القيمة المكتسبة (EV)')
                                    ->money('JOD'),
                                Infolists\Components\TextEntry::make('actual_cost')
                                    ->label('التكلفة الفعلية (AC)')
                                    ->money('JOD'),
                            ]),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('schedule_variance')
                                    ->label('فرق الجدول (SV)')
                                    ->money('JOD')
                                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                                Infolists\Components\TextEntry::make('cost_variance')
                                    ->label('فرق التكلفة (CV)')
                                    ->money('JOD')
                                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                                Infolists\Components\TextEntry::make('variance_at_completion')
                                    ->label('الفرق عند الإنجاز (VAC)')
                                    ->money('JOD')
                                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                            ]),
                    ]),

                Infolists\Components\Section::make('التنبؤات')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('estimate_at_completion')
                                    ->label('التكلفة المتوقعة (EAC)')
                                    ->money('JOD'),
                                Infolists\Components\TextEntry::make('estimate_to_complete')
                                    ->label('المتبقي للإنجاز (ETC)')
                                    ->money('JOD'),
                                Infolists\Components\TextEntry::make('estimated_completion_date')
                                    ->label('التاريخ المتوقع للإنجاز')
                                    ->date(),
                            ]),
                    ]),
            ]);
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
            'index' => Pages\ListEvmMeasurements::route('/'),
            'create' => Pages\CreateEvmMeasurement::route('/create'),
            'view' => Pages\ViewEvmMeasurement::route('/{record}'),
            'edit' => Pages\EditEvmMeasurement::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
