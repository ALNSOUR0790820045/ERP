<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BimModelResource\Pages;
use App\Models\BimModel;
use App\Services\ProjectManagement\BimIntegrationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class BimModelResource extends Resource
{
    protected static ?string $model = BimModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'نماذج BIM';
    protected static ?string $modelLabel = 'نموذج BIM';
    protected static ?string $pluralModelLabel = 'نماذج BIM';
    protected static ?string $navigationGroup = 'المشاريع والعقود';
    protected static ?int $navigationSort = 88;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات النموذج')
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->label('المشروع')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\TextInput::make('name')
                            ->label('اسم النموذج')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->rows(2),
                        
                        Forms\Components\Select::make('model_type')
                            ->label('نوع النموذج')
                            ->options([
                                'architectural' => 'معماري',
                                'structural' => 'إنشائي',
                                'mep' => 'ميكانيكي/كهربائي/صحي',
                                'combined' => 'مدمج',
                                'coordination' => 'تنسيق',
                            ])
                            ->default('combined')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('ملف النموذج')
                    ->schema([
                        Forms\Components\FileUpload::make('file_path')
                            ->label('ملف BIM')
                            ->directory('bim/models')
                            ->acceptedFileTypes(['.ifc', '.rvt', '.nwd', '.nwc', 'application/octet-stream'])
                            ->maxSize(512000) // 500MB
                            ->required(),
                        
                        Forms\Components\TextInput::make('software_name')
                            ->label('اسم البرنامج')
                            ->placeholder('Revit, ArchiCAD, Navisworks...'),
                        
                        Forms\Components\TextInput::make('software_version')
                            ->label('إصدار البرنامج'),
                        
                        Forms\Components\Select::make('lod')
                            ->label('مستوى التطوير (LOD)')
                            ->options([
                                '100' => 'LOD 100 - مفاهيمي',
                                '200' => 'LOD 200 - تقريبي',
                                '300' => 'LOD 300 - دقيق',
                                '350' => 'LOD 350 - تنسيق',
                                '400' => 'LOD 400 - تصنيع',
                                '500' => 'LOD 500 - كما تم البناء',
                            ]),
                    ])->columns(2),

                Forms\Components\Section::make('إعدادات الربط')
                    ->schema([
                        Forms\Components\Toggle::make('enable_4d')
                            ->label('تفعيل 4D (ربط الجدول الزمني)')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('enable_5d')
                            ->label('تفعيل 5D (ربط التكاليف)')
                            ->default(true),
                        
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
                Tables\Columns\TextColumn::make('project.name')
                    ->label('المشروع')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم النموذج')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('model_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'architectural' => 'معماري',
                        'structural' => 'إنشائي',
                        'mep' => 'MEP',
                        'combined' => 'مدمج',
                        'coordination' => 'تنسيق',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('file_format')
                    ->label('الصيغة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => strtoupper($state)),
                
                Tables\Columns\TextColumn::make('lod')
                    ->label('LOD')
                    ->badge(),
                
                Tables\Columns\TextColumn::make('elements_count')
                    ->label('عدد العناصر')
                    ->numeric(),
                
                Tables\Columns\TextColumn::make('file_size')
                    ->label('الحجم')
                    ->formatStateUsing(fn ($state) => number_format($state / 1024 / 1024, 2) . ' MB'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الرفع')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'name'),
                
                Tables\Filters\SelectFilter::make('model_type')
                    ->label('نوع النموذج')
                    ->options([
                        'architectural' => 'معماري',
                        'structural' => 'إنشائي',
                        'mep' => 'MEP',
                        'combined' => 'مدمج',
                        'coordination' => 'تنسيق',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('نشط'),
            ])
            ->actions([
                Tables\Actions\Action::make('sync_progress')
                    ->label('مزامنة التقدم')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (BimModel $record) {
                        try {
                            $service = app(BimIntegrationService::class);
                            $service->syncProgressFromTasks($record->id);
                            
                            Notification::make()
                                ->success()
                                ->title('تمت المزامنة بنجاح')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('خطأ في المزامنة')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('view_4d')
                    ->label('عرض 4D')
                    ->icon('heroicon-o-film')
                    ->color('success')
                    ->url(fn (BimModel $record) => route('bim.4d-view', $record)),
                
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
                Infolists\Components\Section::make('إحصائيات النموذج')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('elements_count')
                                    ->label('إجمالي العناصر'),
                                
                                Infolists\Components\TextEntry::make('elementLinks')
                                    ->label('عناصر مرتبطة بالجدول')
                                    ->getStateUsing(fn (BimModel $record) => $record->elementLinks()->whereNotNull('gantt_task_id')->count()),
                                
                                Infolists\Components\TextEntry::make('boq_linked')
                                    ->label('عناصر مرتبطة بالتكاليف')
                                    ->getStateUsing(fn (BimModel $record) => $record->elementLinks()->whereNotNull('boq_item_id')->count()),
                                
                                Infolists\Components\TextEntry::make('total_cost')
                                    ->label('إجمالي التكلفة')
                                    ->getStateUsing(fn (BimModel $record) => number_format($record->elementLinks()->sum('total_cost'), 2)),
                            ]),
                    ]),

                Infolists\Components\Section::make('معلومات الملف')
                    ->schema([
                        Infolists\Components\TextEntry::make('file_name')
                            ->label('اسم الملف'),
                        
                        Infolists\Components\TextEntry::make('file_format')
                            ->label('الصيغة'),
                        
                        Infolists\Components\TextEntry::make('file_size')
                            ->label('الحجم')
                            ->formatStateUsing(fn ($state) => number_format($state / 1024 / 1024, 2) . ' MB'),
                        
                        Infolists\Components\TextEntry::make('ifc_schema_version')
                            ->label('إصدار IFC'),
                        
                        Infolists\Components\TextEntry::make('software_name')
                            ->label('البرنامج'),
                        
                        Infolists\Components\TextEntry::make('lod')
                            ->label('مستوى التطوير'),
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
            'index' => Pages\ListBimModels::route('/'),
            'create' => Pages\CreateBimModel::route('/create'),
            'view' => Pages\ViewBimModel::route('/{record}'),
            'edit' => Pages\EditBimModel::route('/{record}/edit'),
        ];
    }
}
