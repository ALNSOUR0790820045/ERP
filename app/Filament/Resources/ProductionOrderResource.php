<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionOrderResource\Pages;
use App\Models\ProductionOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductionOrderResource extends Resource
{
    protected static ?string $model = ProductionOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'التصنيع';

    protected static ?string $modelLabel = 'أمر إنتاج';

    protected static ?string $pluralModelLabel = 'أوامر الإنتاج';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات أمر الإنتاج')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('رقم الأمر')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => ProductionOrder::generateNumber()),
                        Forms\Components\DatePicker::make('order_date')
                            ->label('تاريخ الأمر')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('bom_id')
                            ->label('قائمة المواد')
                            ->relationship('bom', 'product_name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('project_id')
                            ->label('المشروع')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('الكميات')
                    ->schema([
                        Forms\Components\TextInput::make('planned_quantity')
                            ->label('الكمية المخططة')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('produced_quantity')
                            ->label('الكمية المنتجة')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('rejected_quantity')
                            ->label('الكمية المرفوضة')
                            ->numeric()
                            ->default(0),
                    ])->columns(3),

                Forms\Components\Section::make('التوقيت')
                    ->schema([
                        Forms\Components\DateTimePicker::make('planned_start')
                            ->label('بداية مخططة'),
                        Forms\Components\DateTimePicker::make('planned_end')
                            ->label('نهاية مخططة'),
                        Forms\Components\DateTimePicker::make('actual_start')
                            ->label('بداية فعلية'),
                        Forms\Components\DateTimePicker::make('actual_end')
                            ->label('نهاية فعلية'),
                    ])->columns(4),

                Forms\Components\Section::make('الحالة')
                    ->schema([
                        Forms\Components\Select::make('priority')
                            ->label('الأولوية')
                            ->options([
                                'low' => 'منخفضة',
                                'normal' => 'عادية',
                                'high' => 'عالية',
                                'urgent' => 'عاجلة',
                            ])
                            ->default('normal'),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'draft' => 'مسودة',
                                'released' => 'مُصدر',
                                'in_progress' => 'قيد التنفيذ',
                                'completed' => 'مكتمل',
                                'cancelled' => 'ملغي',
                                'on_hold' => 'معلق',
                            ])
                            ->default('draft'),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('رقم الأمر')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bom.product_name')
                    ->label('المنتج')
                    ->searchable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('المشروع')
                    ->searchable(),
                Tables\Columns\TextColumn::make('planned_quantity')
                    ->label('الكمية المخططة')
                    ->numeric(),
                Tables\Columns\TextColumn::make('produced_quantity')
                    ->label('الكمية المنتجة')
                    ->numeric(),
                Tables\Columns\BadgeColumn::make('priority')
                    ->label('الأولوية')
                    ->colors([
                        'gray' => 'low',
                        'info' => 'normal',
                        'warning' => 'high',
                        'danger' => 'urgent',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'released',
                        'warning' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                        'secondary' => 'on_hold',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'released' => 'مُصدر',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                        'on_hold' => 'معلق',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('الأولوية')
                    ->options([
                        'low' => 'منخفضة',
                        'normal' => 'عادية',
                        'high' => 'عالية',
                        'urgent' => 'عاجلة',
                    ]),
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
            'index' => Pages\ListProductionOrders::route('/'),
            'create' => Pages\CreateProductionOrder::route('/create'),
            'edit' => Pages\EditProductionOrder::route('/{record}/edit'),
        ];
    }
}
