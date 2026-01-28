<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GanttTaskResource\Pages;
use App\Models\GanttTask;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GanttTaskResource extends Resource
{
    protected static ?string $model = GanttTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'إدارة المشاريع';

    protected static ?string $navigationLabel = 'Gantt Chart';

    protected static ?string $modelLabel = 'مهمة Gantt';

    protected static ?string $pluralModelLabel = 'مهام Gantt';

    protected static ?int $navigationSort = 25;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المهمة')
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->label('المشروع')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('parent_id')
                            ->label('المهمة الأب')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('task_code')
                            ->label('رمز المهمة')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('name')
                            ->label('اسم المهمة')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('wbs_code')
                            ->label('رمز WBS')
                            ->maxLength(50),
                    ])->columns(2),

                Forms\Components\Section::make('التواريخ والمدة')
                    ->schema([
                        Forms\Components\DatePicker::make('planned_start')
                            ->label('تاريخ البداية المخطط')
                            ->required(),

                        Forms\Components\DatePicker::make('planned_end')
                            ->label('تاريخ النهاية المخطط')
                            ->required()
                            ->afterOrEqual('planned_start'),

                        Forms\Components\DatePicker::make('actual_start')
                            ->label('تاريخ البداية الفعلي'),

                        Forms\Components\DatePicker::make('actual_end')
                            ->label('تاريخ النهاية الفعلي')
                            ->afterOrEqual('actual_start'),

                        Forms\Components\TextInput::make('duration_days')
                            ->label('المدة (أيام)')
                            ->numeric()
                            ->default(1)
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('التقدم والأولوية')
                    ->schema([
                        Forms\Components\TextInput::make('progress')
                            ->label('نسبة التقدم (%)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%'),

                        Forms\Components\TextInput::make('weight')
                            ->label('الوزن (%)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100),

                        Forms\Components\Select::make('task_type')
                            ->label('نوع المهمة')
                            ->options(GanttTask::TASK_TYPES)
                            ->default('task')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(GanttTask::STATUSES)
                            ->default('not_started')
                            ->required(),

                        Forms\Components\Select::make('priority')
                            ->label('الأولوية')
                            ->options(GanttTask::PRIORITIES)
                            ->default('medium')
                            ->required(),

                        Forms\Components\Toggle::make('is_critical')
                            ->label('مهمة حرجة')
                            ->default(false),
                    ])->columns(3),

                Forms\Components\Section::make('الموارد والتكاليف')
                    ->schema([
                        Forms\Components\Select::make('assigned_to')
                            ->label('المسؤول')
                            ->relationship('assignedTo', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('estimated_hours')
                            ->label('الساعات المقدرة')
                            ->numeric(),

                        Forms\Components\TextInput::make('actual_hours')
                            ->label('الساعات الفعلية')
                            ->numeric(),

                        Forms\Components\TextInput::make('estimated_cost')
                            ->label('التكلفة المقدرة')
                            ->numeric()
                            ->prefix('JOD'),

                        Forms\Components\TextInput::make('actual_cost')
                            ->label('التكلفة الفعلية')
                            ->numeric()
                            ->prefix('JOD'),

                        Forms\Components\ColorPicker::make('color')
                            ->label('اللون'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('task_code')
                    ->label('الرمز')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('المهمة')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('المشروع')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('planned_start')
                    ->label('البداية')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('planned_end')
                    ->label('النهاية')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_days')
                    ->label('المدة')
                    ->suffix(' يوم')
                    ->sortable(),

                Tables\Columns\TextColumn::make('progress')
                    ->label('التقدم')
                    ->suffix('%')
                    ->color(fn ($record) => 
                        $record->progress >= 100 ? 'success' : 
                        ($record->progress >= 50 ? 'info' : 'warning')
                    ),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'gray' => 'not_started',
                        'info' => 'in_progress',
                        'success' => 'completed',
                        'warning' => 'on_hold',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => GanttTask::STATUSES[$state] ?? $state),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label('الأولوية')
                    ->colors([
                        'gray' => 'low',
                        'info' => 'medium',
                        'warning' => 'high',
                        'danger' => 'critical',
                    ])
                    ->formatStateUsing(fn ($state) => GanttTask::PRIORITIES[$state] ?? $state),

                Tables\Columns\IconColumn::make('is_critical')
                    ->label('حرجة')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('danger'),

                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('المسؤول')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(GanttTask::STATUSES),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('الأولوية')
                    ->options(GanttTask::PRIORITIES),

                Tables\Filters\SelectFilter::make('task_type')
                    ->label('النوع')
                    ->options(GanttTask::TASK_TYPES),

                Tables\Filters\TernaryFilter::make('is_critical')
                    ->label('المهام الحرجة'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
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
            'index' => Pages\ListGanttTasks::route('/'),
            'create' => Pages\CreateGanttTask::route('/create'),
            'view' => Pages\ViewGanttTask::route('/{record}'),
            'edit' => Pages\EditGanttTask::route('/{record}/edit'),
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
