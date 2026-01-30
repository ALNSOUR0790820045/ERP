<?php

namespace App\Filament\Resources\TenderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class BoqItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'boqItems';

    protected static ?string $title = 'جدول الكميات (BOQ)';
    
    protected static ?string $modelLabel = 'بند';
    
    protected static ?string $pluralModelLabel = 'بنود جدول الكميات';

    protected static ?string $icon = 'heroicon-o-table-cells';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات البند')
                    ->icon('heroicon-o-document-text')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('item_number')
                            ->label('رقم البند')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('مثال: 1.1.1'),
                            
                        Forms\Components\Select::make('category')
                            ->label('التصنيف')
                            ->options([
                                'civil' => 'أعمال مدنية',
                                'mechanical' => 'أعمال ميكانيكية',
                                'electrical' => 'أعمال كهربائية',
                                'plumbing' => 'أعمال صحية',
                                'finishing' => 'أعمال تشطيب',
                                'external' => 'أعمال خارجية',
                                'other' => 'أخرى',
                            ])
                            ->native(false),
                            
                        Forms\Components\Select::make('parent_id')
                            ->label('البند الرئيسي')
                            ->relationship('parent', 'item_number')
                            ->searchable()
                            ->preload()
                            ->placeholder('اختر إذا كان بند فرعي'),
                    ]),
                    
                Forms\Components\Section::make('الوصف')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف بالعربية')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull()
                            ->placeholder('وصف تفصيلي للبند'),
                            
                        Forms\Components\Textarea::make('description_en')
                            ->label('الوصف بالإنجليزية')
                            ->rows(2)
                            ->columnSpanFull()
                            ->placeholder('English description'),
                            
                        Forms\Components\Textarea::make('specifications')
                            ->label('المواصفات الفنية')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('الكميات والتسعير')
                    ->icon('heroicon-o-calculator')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Select::make('unit_id')
                            ->label('الوحدة')
                            ->relationship('unit', 'name_ar')
                            ->searchable()
                            ->preload()
                            ->required(),
                            
                        Forms\Components\TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => 
                                $set('total_price', number_format(($get('quantity') ?? 0) * ($get('unit_price') ?? 0), 3, '.', ''))
                            ),
                            
                        Forms\Components\TextInput::make('unit_price')
                            ->label('سعر الوحدة')
                            ->numeric()
                            ->prefix('د.أ')
                            ->minValue(0)
                            ->step(0.001)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => 
                                $set('total_price', number_format(($get('quantity') ?? 0) * ($get('unit_price') ?? 0), 3, '.', ''))
                            ),
                            
                        Forms\Components\TextInput::make('total_price')
                            ->label('الإجمالي')
                            ->numeric()
                            ->prefix('د.أ')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
                    
                Forms\Components\Section::make('تحليل التكلفة')
                    ->icon('heroicon-o-chart-pie')
                    ->columns(4)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('material_cost')
                            ->label('تكلفة المواد')
                            ->numeric()
                            ->prefix('د.أ')
                            ->minValue(0),
                            
                        Forms\Components\TextInput::make('labor_cost')
                            ->label('تكلفة العمالة')
                            ->numeric()
                            ->prefix('د.أ')
                            ->minValue(0),
                            
                        Forms\Components\TextInput::make('equipment_cost')
                            ->label('تكلفة المعدات')
                            ->numeric()
                            ->prefix('د.أ')
                            ->minValue(0),
                            
                        Forms\Components\TextInput::make('overhead_percentage')
                            ->label('نسبة المصاريف العامة %')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(10),
                    ]),
                    
                Forms\Components\Section::make('ملاحظات')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item_number')
            ->reorderable('sort_order')
            ->defaultSort('item_number')
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('item_number')
                    ->label('رقم البند')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('تم نسخ رقم البند'),
                    
                Tables\Columns\TextColumn::make('category')
                    ->label('التصنيف')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'civil' => 'مدني',
                        'mechanical' => 'ميكانيكي',
                        'electrical' => 'كهربائي',
                        'plumbing' => 'صحي',
                        'finishing' => 'تشطيب',
                        'external' => 'خارجي',
                        default => 'أخرى',
                    })
                    ->color(fn ($state) => match($state) {
                        'civil' => 'gray',
                        'mechanical' => 'warning',
                        'electrical' => 'danger',
                        'plumbing' => 'info',
                        'finishing' => 'success',
                        'external' => 'primary',
                        default => 'gray',
                    })
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(50)
                    ->wrap()
                    ->searchable()
                    ->tooltip(fn ($state) => $state),
                    
                Tables\Columns\TextColumn::make('unit.symbol')
                    ->label('الوحدة')
                    ->badge()
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('quantity')
                    ->label('الكمية')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->alignEnd(),
                    
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('سعر الوحدة')
                    ->money('JOD')
                    ->sortable()
                    ->alignEnd()
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('total_price')
                    ->label('الإجمالي')
                    ->money('JOD')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color('success')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('JOD')
                            ->label('المجموع'),
                    ]),
                    
                Tables\Columns\TextColumn::make('profit_margin')
                    ->label('هامش الربح')
                    ->formatStateUsing(function ($record) {
                        if (!$record->material_cost && !$record->labor_cost && !$record->equipment_cost) {
                            return '-';
                        }
                        $cost = ($record->material_cost ?? 0) + ($record->labor_cost ?? 0) + ($record->equipment_cost ?? 0);
                        if ($cost == 0) return '-';
                        $margin = (($record->unit_price - $cost) / $cost) * 100;
                        return number_format($margin, 1) . '%';
                    })
                    ->color(fn ($record) => 
                        ($record->material_cost ?? 0) + ($record->labor_cost ?? 0) + ($record->equipment_cost ?? 0) > 0
                            ? (($record->unit_price ?? 0) > (($record->material_cost ?? 0) + ($record->labor_cost ?? 0) + ($record->equipment_cost ?? 0)) ? 'success' : 'danger')
                            : 'gray'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('التصنيف')
                    ->options([
                        'civil' => 'أعمال مدنية',
                        'mechanical' => 'أعمال ميكانيكية',
                        'electrical' => 'أعمال كهربائية',
                        'plumbing' => 'أعمال صحية',
                        'finishing' => 'أعمال تشطيب',
                        'external' => 'أعمال خارجية',
                        'other' => 'أخرى',
                    ]),
                    
                Tables\Filters\Filter::make('has_price')
                    ->label('تم التسعير')
                    ->query(fn ($query) => $query->whereNotNull('unit_price')->where('unit_price', '>', 0))
                    ->toggle(),
                    
                Tables\Filters\Filter::make('no_price')
                    ->label('بدون تسعير')
                    ->query(fn ($query) => $query->whereNull('unit_price')->orWhere('unit_price', '=', 0))
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->label('إضافة بند'),
                    
                Tables\Actions\Action::make('import')
                    ->label('استيراد من Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('ملف Excel')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv'])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        Notification::make()
                            ->title('جاري تطوير خاصية الاستيراد')
                            ->info()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('apply_markup')
                    ->label('تطبيق هامش ربح')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('markup_percentage')
                            ->label('نسبة الزيادة %')
                            ->numeric()
                            ->required()
                            ->suffix('%')
                            ->default(15),
                        Forms\Components\Checkbox::make('apply_to_all')
                            ->label('تطبيق على جميع البنود')
                            ->default(true),
                    ])
                    ->action(function (array $data) {
                        $tender = $this->getOwnerRecord();
                        $query = $tender->boqItems();
                        
                        if ($data['apply_to_all']) {
                            $query->whereNotNull('unit_price')
                                ->where('unit_price', '>', 0)
                                ->each(function ($item) use ($data) {
                                    $item->update([
                                        'unit_price' => $item->unit_price * (1 + ($data['markup_percentage'] / 100))
                                    ]);
                                });
                        }
                        
                        Notification::make()
                            ->title('تم تطبيق هامش الربح')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('summary')
                    ->label('ملخص التسعير')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('info')
                    ->modalHeading('ملخص جدول الكميات')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalContent(function () {
                        $tender = $this->getOwnerRecord();
                        $items = $tender->boqItems;
                        
                        $totalItems = $items->count();
                        $pricedItems = $items->whereNotNull('unit_price')->where('unit_price', '>', 0)->count();
                        $totalValue = $items->sum(fn ($i) => ($i->quantity ?? 0) * ($i->unit_price ?? 0));
                        
                        return new HtmlString("
                            <div class='space-y-4 text-right'>
                                <div class='grid grid-cols-3 gap-4'>
                                    <div class='bg-primary-50 dark:bg-primary-900/20 p-4 rounded-lg'>
                                        <div class='text-2xl font-bold text-primary-600'>{$totalItems}</div>
                                        <div class='text-sm text-gray-600'>إجمالي البنود</div>
                                    </div>
                                    <div class='bg-success-50 dark:bg-success-900/20 p-4 rounded-lg'>
                                        <div class='text-2xl font-bold text-success-600'>{$pricedItems}</div>
                                        <div class='text-sm text-gray-600'>تم تسعيرها</div>
                                    </div>
                                    <div class='bg-warning-50 dark:bg-warning-900/20 p-4 rounded-lg'>
                                        <div class='text-2xl font-bold text-warning-600'>" . ($totalItems - $pricedItems) . "</div>
                                        <div class='text-sm text-gray-600'>بدون تسعير</div>
                                    </div>
                                </div>
                                <div class='bg-gray-100 dark:bg-gray-800 p-4 rounded-lg'>
                                    <div class='text-3xl font-bold text-gray-900 dark:text-white'>" . number_format($totalValue, 3) . " د.أ</div>
                                    <div class='text-sm text-gray-600'>إجمالي قيمة جدول الكميات</div>
                                </div>
                            </div>
                        ");
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ReplicateAction::make()
                        ->label('نسخ')
                        ->excludeAttributes(['created_at', 'updated_at']),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('apply_category')
                        ->label('تعيين تصنيف')
                        ->icon('heroicon-o-tag')
                        ->form([
                            Forms\Components\Select::make('category')
                                ->label('التصنيف')
                                ->options([
                                    'civil' => 'أعمال مدنية',
                                    'mechanical' => 'أعمال ميكانيكية',
                                    'electrical' => 'أعمال كهربائية',
                                    'plumbing' => 'أعمال صحية',
                                    'finishing' => 'أعمال تشطيب',
                                    'external' => 'أعمال خارجية',
                                    'other' => 'أخرى',
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each->update(['category' => $data['category']]);
                            Notification::make()->title('تم تحديث التصنيف')->success()->send();
                        }),
                    Tables\Actions\BulkAction::make('export')
                        ->label('تصدير المحدد')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            Notification::make()
                                ->title('جاري تطوير خاصية التصدير')
                                ->info()
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('لا توجد بنود')
            ->emptyStateDescription('أضف بنود جدول الكميات أو قم باستيرادها من ملف Excel')
            ->emptyStateIcon('heroicon-o-table-cells')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة بند جديد')
                    ->icon('heroicon-o-plus'),
            ]);
    }
}
