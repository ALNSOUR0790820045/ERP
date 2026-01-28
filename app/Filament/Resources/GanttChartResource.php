<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GanttChartResource\Pages;
use App\Models\GanttChart;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GanttChartResource extends Resource
{
    protected static ?string $model = GanttChart::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationGroup = 'إدارة المشاريع';
    
    protected static ?string $modelLabel = 'مخطط جانت';
    
    protected static ?string $pluralModelLabel = 'مخططات جانت';
    
    protected static ?int $navigationSort = 10;

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
                            ->required(),
                            
                        Forms\Components\Select::make('parent_id')
                            ->label('المهمة الأب')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->nullable(),
                            
                        Forms\Components\TextInput::make('task_code')
                            ->label('رمز المهمة')
                            ->maxLength(50),
                            
                        Forms\Components\TextInput::make('name')
                            ->label('اسم المهمة')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(4),
                    
                Forms\Components\Section::make('التواريخ')
                    ->schema([
                        Forms\Components\DatePicker::make('planned_start')
                            ->label('البداية المخططة')
                            ->required(),
                            
                        Forms\Components\DatePicker::make('planned_end')
                            ->label('النهاية المخططة')
                            ->required(),
                            
                        Forms\Components\DatePicker::make('actual_start')
                            ->label('البداية الفعلية'),
                            
                        Forms\Components\DatePicker::make('actual_end')
                            ->label('النهاية الفعلية'),
                            
                        Forms\Components\TextInput::make('duration_days')
                            ->label('المدة (أيام)')
                            ->numeric()
                            ->default(1)
                            ->required(),
                    ])->columns(5),
                    
                Forms\Components\Section::make('التقدم والأولوية')
                    ->schema([
                        Forms\Components\TextInput::make('progress')
                            ->label('نسبة الإنجاز %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(0),
                            
                        Forms\Components\TextInput::make('weight')
                            ->label('الوزن')
                            ->numeric()
                            ->default(0),
                            
                        Forms\Components\Select::make('task_type')
                            ->label('نوع المهمة')
                            ->options([
                                'task' => 'مهمة',
                                'milestone' => 'معلم',
                                'summary' => 'ملخص',
                                'project' => 'مشروع',
                            ])
                            ->default('task')
                            ->required(),
                            
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'not_started' => 'لم يبدأ',
                                'in_progress' => 'قيد التنفيذ',
                                'completed' => 'مكتمل',
                                'on_hold' => 'متوقف',
                                'cancelled' => 'ملغي',
                            ])
                            ->default('not_started')
                            ->required(),
                            
                        Forms\Components\Select::make('priority')
                            ->label('الأولوية')
                            ->options([
                                'low' => 'منخفض',
                                'medium' => 'متوسط',
                                'high' => 'عالي',
                                'critical' => 'حرج',
                            ])
                            ->default('medium')
                            ->required(),
                            
                        Forms\Components\Toggle::make('is_critical')
                            ->label('مسار حرج')
                            ->default(false),
                    ])->columns(6),
                    
                Forms\Components\Section::make('الموارد والتكاليف')
                    ->schema([
                        Forms\Components\Select::make('assigned_to')
                            ->label('مُسند إلى')
                            ->relationship('assignedUser', 'name')
                            ->searchable(),
                            
                        Forms\Components\TextInput::make('estimated_hours')
                            ->label('الساعات المقدرة')
                            ->numeric(),
                            
                        Forms\Components\TextInput::make('actual_hours')
                            ->label('الساعات الفعلية')
                            ->numeric(),
                            
                        Forms\Components\TextInput::make('estimated_cost')
                            ->label('التكلفة المقدرة')
                            ->numeric()
                            ->prefix('د.أ'),
                            
                        Forms\Components\TextInput::make('actual_cost')
                            ->label('التكلفة الفعلية')
                            ->numeric()
                            ->prefix('د.أ'),
                            
                        Forms\Components\ColorPicker::make('color')
                            ->label('اللون'),
                    ])->columns(6),
                    
                Forms\Components\Section::make('معلومات إضافية')
                    ->schema([
                        Forms\Components\TextInput::make('wbs_code')
                            ->label('رمز WBS')
                            ->maxLength(50),
                            
                        Forms\Components\TextInput::make('sort_order')
                            ->label('الترتيب')
                            ->numeric()
                            ->default(0),
                            
                        Forms\Components\TextInput::make('level')
                            ->label('المستوى')
                            ->numeric()
                            ->default(0),
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
                    ->label('الإنجاز')
                    ->suffix('%')
                    ->sortable()
                    ->color(fn ($state) => $state >= 100 ? 'success' : ($state >= 50 ? 'warning' : 'gray')),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'not_started' => 'لم يبدأ',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'on_hold' => 'متوقف',
                        'cancelled' => 'ملغي',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'not_started' => 'gray',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'on_hold' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\IconColumn::make('is_critical')
                    ->label('حرج')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('danger'),
                    
                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('المسؤول')
                    ->toggleable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'not_started' => 'لم يبدأ',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'on_hold' => 'متوقف',
                        'cancelled' => 'ملغي',
                    ]),
                Tables\Filters\SelectFilter::make('task_type')
                    ->label('النوع')
                    ->options([
                        'task' => 'مهمة',
                        'milestone' => 'معلم',
                        'summary' => 'ملخص',
                    ]),
                Tables\Filters\TernaryFilter::make('is_critical')
                    ->label('المسار الحرج'),
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
            'index' => Pages\ListGanttCharts::route('/'),
            'create' => Pages\CreateGanttChart::route('/create'),
            'edit' => Pages\EditGanttChart::route('/{record}/edit'),
        ];
    }
}
