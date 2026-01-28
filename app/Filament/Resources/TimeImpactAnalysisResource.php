<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimeImpactAnalysisResource\Pages;
use App\Models\TimeImpactAnalysis;
use App\Services\ProjectManagement\TimeImpactAnalysisService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class TimeImpactAnalysisResource extends Resource
{
    protected static ?string $model = TimeImpactAnalysis::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'تحليل التأثير الزمني';
    protected static ?string $modelLabel = 'تحليل التأثير الزمني';
    protected static ?string $pluralModelLabel = 'تحليلات التأثير الزمني';
    protected static ?string $navigationGroup = 'المشاريع';
    protected static ?int $navigationSort = 87;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات التحليل')
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->label('المشروع')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان التحليل')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->rows(2),
                        
                        Forms\Components\Select::make('extension_of_time_id')
                            ->label('طلب تمديد الوقت المرتبط')
                            ->relationship('extensionOfTime', 'reference_number')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('نوع التأخير وطريقة التحليل')
                    ->schema([
                        Forms\Components\Select::make('delay_type')
                            ->label('نوع التأخير')
                            ->options([
                                'excusable_compensable' => 'معذور قابل للتعويض',
                                'excusable_non_compensable' => 'معذور غير قابل للتعويض',
                                'non_excusable' => 'غير معذور',
                                'concurrent' => 'متزامن',
                            ])
                            ->default('excusable_compensable')
                            ->required(),
                        
                        Forms\Components\Select::make('analysis_method')
                            ->label('طريقة التحليل')
                            ->options([
                                'time_impact' => 'تحليل التأثير الزمني (TIA)',
                                'as_planned_impacted' => 'المخطط المتأثر',
                                'collapsed_as_built' => 'البناء المنهار',
                                'window_analysis' => 'تحليل النوافذ',
                            ])
                            ->default('time_impact')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('التواريخ')
                    ->schema([
                        Forms\Components\DatePicker::make('event_start_date')
                            ->label('تاريخ بداية الحدث')
                            ->required(),
                        
                        Forms\Components\DatePicker::make('event_end_date')
                            ->label('تاريخ نهاية الحدث'),
                        
                        Forms\Components\DatePicker::make('data_date')
                            ->label('تاريخ البيانات')
                            ->default(now()),
                        
                        Forms\Components\DatePicker::make('baseline_completion_date')
                            ->label('تاريخ الإكمال الأساسي'),
                    ])->columns(4),

                Forms\Components\Section::make('الأجزاء المضافة (Fragments)')
                    ->schema([
                        Forms\Components\Repeater::make('fragments')
                            ->relationship('fragments')
                            ->label('الأجزاء')
                            ->schema([
                                Forms\Components\TextInput::make('fragment_name')
                                    ->label('اسم الجزء')
                                    ->required(),
                                
                                Forms\Components\Select::make('predecessor_task_id')
                                    ->label('النشاط السابق')
                                    ->relationship('predecessorTask', 'name')
                                    ->searchable(),
                                
                                Forms\Components\Select::make('successor_task_id')
                                    ->label('النشاط اللاحق')
                                    ->relationship('successorTask', 'name')
                                    ->searchable(),
                                
                                Forms\Components\DatePicker::make('fragment_start_date')
                                    ->label('تاريخ البداية')
                                    ->required(),
                                
                                Forms\Components\DatePicker::make('fragment_end_date')
                                    ->label('تاريخ النهاية')
                                    ->required(),
                                
                                Forms\Components\TextInput::make('fragment_duration')
                                    ->label('المدة (أيام)')
                                    ->numeric(),
                                
                                Forms\Components\Select::make('dependency_type')
                                    ->label('نوع العلاقة')
                                    ->options([
                                        'FS' => 'نهاية-بداية (FS)',
                                        'SS' => 'بداية-بداية (SS)',
                                        'FF' => 'نهاية-نهاية (FF)',
                                        'SF' => 'بداية-نهاية (SF)',
                                    ])
                                    ->default('FS'),
                                
                                Forms\Components\TextInput::make('lag_days')
                                    ->label('التأخر (أيام)')
                                    ->numeric()
                                    ->default(0),
                                
                                Forms\Components\Textarea::make('description')
                                    ->label('الوصف')
                                    ->rows(1),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->defaultItems(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('analysis_number')
                    ->label('رقم التحليل')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('project.name')
                    ->label('المشروع')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('delay_type')
                    ->label('نوع التأخير')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'excusable_compensable' => 'معذور قابل للتعويض',
                        'excusable_non_compensable' => 'معذور غير قابل للتعويض',
                        'non_excusable' => 'غير معذور',
                        'concurrent' => 'متزامن',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'excusable_compensable' => 'success',
                        'excusable_non_compensable' => 'warning',
                        'non_excusable' => 'danger',
                        'concurrent' => 'info',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'draft' => 'مسودة',
                        'submitted' => 'مقدم',
                        'approved' => 'معتمد',
                        'rejected' => 'مرفوض',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('delay_days')
                    ->label('أيام التأخير')
                    ->numeric()
                    ->suffix(' يوم'),
                
                Tables\Columns\TextColumn::make('net_delay_days')
                    ->label('صافي التأخير')
                    ->numeric()
                    ->suffix(' يوم'),
                
                Tables\Columns\TextColumn::make('event_start_date')
                    ->label('تاريخ الحدث')
                    ->date('Y-m-d'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
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
                        'draft' => 'مسودة',
                        'submitted' => 'مقدم',
                        'approved' => 'معتمد',
                        'rejected' => 'مرفوض',
                    ]),
                
                Tables\Filters\SelectFilter::make('delay_type')
                    ->label('نوع التأخير')
                    ->options([
                        'excusable_compensable' => 'معذور قابل للتعويض',
                        'excusable_non_compensable' => 'معذور غير قابل للتعويض',
                        'non_excusable' => 'غير معذور',
                        'concurrent' => 'متزامن',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('run')
                    ->label('تشغيل التحليل')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (TimeImpactAnalysis $record) => $record->status === 'draft')
                    ->action(function (TimeImpactAnalysis $record) {
                        try {
                            $service = app(TimeImpactAnalysisService::class);
                            $service->runAnalysis($record->id);
                            $service->generateNarrative($record->id);
                            
                            Notification::make()
                                ->success()
                                ->title('تم تشغيل التحليل بنجاح')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('خطأ في تشغيل التحليل')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('submit')
                    ->label('تقديم للاعتماد')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn (TimeImpactAnalysis $record) => $record->status === 'draft' && $record->delay_days !== null)
                    ->requiresConfirmation()
                    ->action(function (TimeImpactAnalysis $record) {
                        $service = app(TimeImpactAnalysisService::class);
                        $service->submitForReview($record->id);
                        
                        Notification::make()
                            ->success()
                            ->title('تم التقديم للاعتماد')
                            ->send();
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
                Infolists\Components\Section::make('نتائج التحليل')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('delay_days')
                                    ->label('إجمالي التأخير')
                                    ->suffix(' يوم')
                                    ->color('danger'),
                                
                                Infolists\Components\TextEntry::make('concurrent_delay_days')
                                    ->label('تأخير متزامن')
                                    ->suffix(' يوم'),
                                
                                Infolists\Components\TextEntry::make('pacing_delay_days')
                                    ->label('تأخير موازنة')
                                    ->suffix(' يوم'),
                                
                                Infolists\Components\TextEntry::make('net_delay_days')
                                    ->label('صافي التأخير')
                                    ->suffix(' يوم')
                                    ->color('success'),
                            ]),
                    ])
                    ->visible(fn (TimeImpactAnalysis $record) => $record->delay_days !== null),

                Infolists\Components\Section::make('التواريخ')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('baseline_completion_date')
                                    ->label('تاريخ الإكمال الأساسي')
                                    ->date('Y-m-d'),
                                
                                Infolists\Components\TextEntry::make('impacted_completion_date')
                                    ->label('تاريخ الإكمال المتأثر')
                                    ->date('Y-m-d'),
                                
                                Infolists\Components\TextEntry::make('data_date')
                                    ->label('تاريخ البيانات')
                                    ->date('Y-m-d'),
                            ]),
                    ]),

                Infolists\Components\Section::make('السرد التحليلي')
                    ->schema([
                        Infolists\Components\TextEntry::make('analysis_narrative')
                            ->label('')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (TimeImpactAnalysis $record) => $record->analysis_narrative !== null),
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
            'index' => Pages\ListTimeImpactAnalyses::route('/'),
            'create' => Pages\CreateTimeImpactAnalysis::route('/create'),
            'view' => Pages\ViewTimeImpactAnalysis::route('/{record}'),
            'edit' => Pages\EditTimeImpactAnalysis::route('/{record}/edit'),
        ];
    }
}
