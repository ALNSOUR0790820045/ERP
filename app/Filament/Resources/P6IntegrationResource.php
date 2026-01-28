<?php

namespace App\Filament\Resources;

use App\Filament\Resources\P6IntegrationResource\Pages;
use App\Models\P6ImportExport;
use App\Services\ProjectManagement\P6ImportService;
use App\Services\ProjectManagement\P6ExportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class P6IntegrationResource extends Resource
{
    protected static ?string $model = P6ImportExport::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationLabel = 'تكامل P6';
    protected static ?string $modelLabel = 'تكامل Primavera P6';
    protected static ?string $pluralModelLabel = 'تكاملات Primavera P6';
    protected static ?string $navigationGroup = 'المشاريع';
    protected static ?int $navigationSort = 85;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات التكامل')
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->label('المشروع')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\Select::make('operation_type')
                            ->label('نوع العملية')
                            ->options([
                                'import' => 'استيراد',
                                'export' => 'تصدير',
                            ])
                            ->required()
                            ->reactive(),
                        
                        Forms\Components\Select::make('file_format')
                            ->label('صيغة الملف')
                            ->options([
                                'xer' => 'XER (Primavera)',
                                'xml' => 'PMXML',
                            ])
                            ->default('xer')
                            ->required(),
                        
                        Forms\Components\FileUpload::make('file_path')
                            ->label('الملف')
                            ->directory('p6/imports')
                            ->acceptedFileTypes(['.xer', 'application/xml', 'text/xml'])
                            ->visible(fn (callable $get) => $get('operation_type') === 'import'),
                    ])->columns(2),

                Forms\Components\Section::make('خيارات الاستيراد')
                    ->schema([
                        Forms\Components\Toggle::make('options.include_resources')
                            ->label('استيراد الموارد')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('options.include_relationships')
                            ->label('استيراد العلاقات')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('options.include_calendars')
                            ->label('استيراد التقويمات')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('options.update_existing')
                            ->label('تحديث الموجود')
                            ->default(false),
                    ])
                    ->visible(fn (callable $get) => $get('operation_type') === 'import')
                    ->columns(2),

                Forms\Components\Section::make('خيارات التصدير')
                    ->schema([
                        Forms\Components\Toggle::make('options.include_wbs')
                            ->label('تضمين WBS')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('options.include_baselines')
                            ->label('تضمين خطوط الأساس')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('options.include_actuals')
                            ->label('تضمين الفعلي')
                            ->default(true),
                    ])
                    ->visible(fn (callable $get) => $get('operation_type') === 'export')
                    ->columns(3),

                Forms\Components\Section::make('ربط البيانات')
                    ->schema([
                        Forms\Components\Repeater::make('mappings')
                            ->label('ربط الأنشطة')
                            ->schema([
                                Forms\Components\TextInput::make('p6_activity_id')
                                    ->label('معرف نشاط P6'),
                                Forms\Components\Select::make('gantt_task_id')
                                    ->label('مهمة المشروع')
                                    ->relationship('ganttTask', 'name')
                                    ->searchable(),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->defaultItems(0),
                    ])
                    ->visible(fn (callable $get) => $get('operation_type') === 'import'),
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
                
                Tables\Columns\TextColumn::make('operation_type')
                    ->label('نوع العملية')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'import' => 'استيراد',
                        'export' => 'تصدير',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'import' => 'info',
                        'export' => 'success',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('file_format')
                    ->label('الصيغة')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => strtoupper($state)),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending' => 'قيد الانتظار',
                        'processing' => 'جاري المعالجة',
                        'completed' => 'مكتمل',
                        'failed' => 'فشل',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('activities_count')
                    ->label('عدد الأنشطة')
                    ->numeric(),
                
                Tables\Columns\TextColumn::make('resources_count')
                    ->label('عدد الموارد')
                    ->numeric(),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'name'),
                
                Tables\Filters\SelectFilter::make('operation_type')
                    ->label('نوع العملية')
                    ->options([
                        'import' => 'استيراد',
                        'export' => 'تصدير',
                    ]),
                
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'processing' => 'جاري المعالجة',
                        'completed' => 'مكتمل',
                        'failed' => 'فشل',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('process')
                    ->label('معالجة')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (P6ImportExport $record) => $record->status === 'pending')
                    ->action(function (P6ImportExport $record) {
                        try {
                            if ($record->operation_type === 'import') {
                                $service = app(P6ImportService::class);
                                if ($record->file_format === 'xer') {
                                    $service->importXer($record->project_id, Storage::disk('public')->path($record->file_path));
                                } else {
                                    $service->importXml($record->project_id, Storage::disk('public')->path($record->file_path));
                                }
                            } else {
                                $service = app(P6ExportService::class);
                                if ($record->file_format === 'xer') {
                                    $service->exportToXer($record->project_id);
                                } else {
                                    $service->exportToXml($record->project_id);
                                }
                            }
                            
                            Notification::make()
                                ->success()
                                ->title('تمت العملية بنجاح')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('خطأ في المعالجة')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('download')
                    ->label('تحميل')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (P6ImportExport $record) => $record->operation_type === 'export' && $record->status === 'completed')
                    ->url(fn (P6ImportExport $record) => Storage::disk('public')->url($record->output_file_path))
                    ->openUrlInNewTab(),
                
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListP6Integrations::route('/'),
            'create' => Pages\CreateP6Integration::route('/create'),
            'view' => Pages\ViewP6Integration::route('/{record}'),
        ];
    }
}
