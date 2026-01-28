<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonteCarloSimulationResource\Pages;
use App\Models\MonteCarloSimulation;
use App\Services\ProjectManagement\MonteCarloService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class MonteCarloSimulationResource extends Resource
{
    protected static ?string $model = MonteCarloSimulation::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'محاكاة مونت كارلو';
    protected static ?string $modelLabel = 'محاكاة مونت كارلو';
    protected static ?string $pluralModelLabel = 'محاكاة مونت كارلو';
    protected static ?string $navigationGroup = 'المشاريع';
    protected static ?int $navigationSort = 86;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المحاكاة')
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->label('المشروع')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\TextInput::make('name')
                            ->label('اسم المحاكاة')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->rows(2),
                        
                        Forms\Components\Select::make('simulation_type')
                            ->label('نوع المحاكاة')
                            ->options([
                                'schedule' => 'الجدول الزمني',
                                'cost' => 'التكلفة',
                                'schedule_cost' => 'الجدول والتكلفة معاً',
                            ])
                            ->default('schedule')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('إعدادات المحاكاة')
                    ->schema([
                        Forms\Components\TextInput::make('iterations')
                            ->label('عدد التكرارات')
                            ->numeric()
                            ->default(1000)
                            ->minValue(100)
                            ->maxValue(100000)
                            ->required()
                            ->helperText('يُنصح بـ 1000-10000 تكرار للحصول على نتائج دقيقة'),
                        
                        Forms\Components\Select::make('distribution_type')
                            ->label('نوع التوزيع')
                            ->options([
                                'triangular' => 'مثلثي (Triangular)',
                                'pert' => 'PERT',
                                'normal' => 'طبيعي (Normal)',
                                'beta' => 'بيتا (Beta)',
                            ])
                            ->default('triangular')
                            ->required(),
                        
                        Forms\Components\TextInput::make('confidence_level')
                            ->label('مستوى الثقة (%)')
                            ->numeric()
                            ->default(80)
                            ->minValue(50)
                            ->maxValue(99)
                            ->suffix('%'),
                        
                        Forms\Components\TextInput::make('random_seed')
                            ->label('بذرة العشوائية')
                            ->numeric()
                            ->helperText('اتركه فارغاً لبذرة عشوائية'),
                    ])->columns(2),

                Forms\Components\Section::make('مدخلات الأنشطة')
                    ->schema([
                        Forms\Components\Toggle::make('auto_generate_inputs')
                            ->label('توليد تلقائي للمدخلات')
                            ->default(true)
                            ->helperText('سيتم توليد تقديرات متفائلة/متشائمة تلقائياً بناءً على المدة المخططة'),
                        
                        Forms\Components\Repeater::make('activityInputs')
                            ->relationship('activityInputs')
                            ->label('مدخلات الأنشطة')
                            ->schema([
                                Forms\Components\Select::make('gantt_task_id')
                                    ->label('النشاط')
                                    ->relationship('ganttTask', 'name')
                                    ->searchable()
                                    ->required(),
                                
                                Forms\Components\TextInput::make('optimistic_duration')
                                    ->label('متفائل')
                                    ->numeric()
                                    ->suffix('يوم'),
                                
                                Forms\Components\TextInput::make('most_likely_duration')
                                    ->label('الأرجح')
                                    ->numeric()
                                    ->suffix('يوم'),
                                
                                Forms\Components\TextInput::make('pessimistic_duration')
                                    ->label('متشائم')
                                    ->numeric()
                                    ->suffix('يوم'),
                            ])
                            ->columns(4)
                            ->collapsible()
                            ->defaultItems(0)
                            ->visible(fn (callable $get) => !$get('auto_generate_inputs')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.name')
                    ->label('المشروع')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المحاكاة')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('simulation_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'schedule' => 'جدول زمني',
                        'cost' => 'تكلفة',
                        'schedule_cost' => 'جدول + تكلفة',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending' => 'قيد الانتظار',
                        'running' => 'جاري التشغيل',
                        'completed' => 'مكتمل',
                        'failed' => 'فشل',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'warning',
                        'running' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('iterations')
                    ->label('التكرارات')
                    ->numeric(),
                
                Tables\Columns\TextColumn::make('p50_finish_date')
                    ->label('P50')
                    ->date('Y-m-d')
                    ->description('تاريخ الانتهاء'),
                
                Tables\Columns\TextColumn::make('p80_finish_date')
                    ->label('P80')
                    ->date('Y-m-d'),
                
                Tables\Columns\TextColumn::make('mean_duration')
                    ->label('متوسط المدة')
                    ->suffix(' يوم'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'name'),
                
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'running' => 'جاري التشغيل',
                        'completed' => 'مكتمل',
                        'failed' => 'فشل',
                    ]),
                
                Tables\Filters\SelectFilter::make('simulation_type')
                    ->label('النوع')
                    ->options([
                        'schedule' => 'جدول زمني',
                        'cost' => 'تكلفة',
                        'schedule_cost' => 'جدول + تكلفة',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('run')
                    ->label('تشغيل')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (MonteCarloSimulation $record) => in_array($record->status, ['pending', 'failed']))
                    ->action(function (MonteCarloSimulation $record) {
                        try {
                            $service = app(MonteCarloService::class);
                            $service->runSimulation($record->id);
                            
                            Notification::make()
                                ->success()
                                ->title('تم تشغيل المحاكاة بنجاح')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('خطأ في تشغيل المحاكاة')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('نتائج المحاكاة')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('p50_finish_date')
                                    ->label('P50 (50% احتمال)')
                                    ->date('Y-m-d')
                                    ->color('info'),
                                
                                Infolists\Components\TextEntry::make('p80_finish_date')
                                    ->label('P80 (80% احتمال)')
                                    ->date('Y-m-d')
                                    ->color('warning'),
                                
                                Infolists\Components\TextEntry::make('p90_finish_date')
                                    ->label('P90 (90% احتمال)')
                                    ->date('Y-m-d')
                                    ->color('danger'),
                                
                                Infolists\Components\TextEntry::make('deterministic_finish_date')
                                    ->label('تاريخ حتمي')
                                    ->date('Y-m-d'),
                            ]),
                    ])
                    ->visible(fn (MonteCarloSimulation $record) => $record->status === 'completed'),

                Infolists\Components\Section::make('إحصائيات المدة')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('mean_duration')
                                    ->label('المتوسط')
                                    ->suffix(' يوم'),
                                
                                Infolists\Components\TextEntry::make('std_deviation')
                                    ->label('الانحراف المعياري')
                                    ->suffix(' يوم'),
                                
                                Infolists\Components\TextEntry::make('min_duration')
                                    ->label('الحد الأدنى')
                                    ->suffix(' يوم'),
                                
                                Infolists\Components\TextEntry::make('max_duration')
                                    ->label('الحد الأقصى')
                                    ->suffix(' يوم'),
                            ]),
                    ])
                    ->visible(fn (MonteCarloSimulation $record) => $record->status === 'completed'),

                Infolists\Components\Section::make('معلومات عامة')
                    ->schema([
                        Infolists\Components\TextEntry::make('project.name')
                            ->label('المشروع'),
                        
                        Infolists\Components\TextEntry::make('name')
                            ->label('اسم المحاكاة'),
                        
                        Infolists\Components\TextEntry::make('simulation_type')
                            ->label('النوع')
                            ->badge(),
                        
                        Infolists\Components\TextEntry::make('iterations')
                            ->label('عدد التكرارات'),
                        
                        Infolists\Components\TextEntry::make('distribution_type')
                            ->label('نوع التوزيع'),
                        
                        Infolists\Components\TextEntry::make('status')
                            ->label('الحالة')
                            ->badge(),
                    ])->columns(3),
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
            'index' => Pages\ListMonteCarloSimulations::route('/'),
            'create' => Pages\CreateMonteCarloSimulation::route('/create'),
            'view' => Pages\ViewMonteCarloSimulation::route('/{record}'),
            'edit' => Pages\EditMonteCarloSimulation::route('/{record}/edit'),
        ];
    }
}
