<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'المشتريات';
    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'أمر شراء';
    }

    public static function getPluralModelLabel(): string
    {
        return 'أوامر الشراء';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات أمر الشراء')->schema([
                Forms\Components\TextInput::make('po_number')
                    ->label('رقم أمر الشراء')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\DatePicker::make('po_date')
                    ->label('تاريخ الأمر')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('supplier_id')
                    ->label('المورد')
                    ->relationship('supplier', 'name_ar')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'name_ar')
                    ->searchable(),
                Forms\Components\Select::make('purchase_request_id')
                    ->label('طلب الشراء')
                    ->relationship('purchaseRequest', 'request_number'),
                Forms\Components\DatePicker::make('delivery_date')
                    ->label('تاريخ التسليم المتوقع'),
            ])->columns(2),

            Forms\Components\Section::make('بنود أمر الشراء')->schema([
                Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('material_id')
                            ->label('المادة')
                            ->relationship('material', 'name_ar')
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('description')
                            ->label('الوصف'),
                        Forms\Components\TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('unit_price')
                            ->label('السعر')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('total_price')
                            ->label('الإجمالي')
                            ->numeric()
                            ->disabled(),
                    ])
                    ->columns(5)
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make('المبالغ والحالة')->schema([
                Forms\Components\TextInput::make('subtotal')
                    ->label('المجموع')
                    ->numeric(),
                Forms\Components\TextInput::make('discount_amount')
                    ->label('الخصم')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('tax_amount')
                    ->label('الضريبة')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total_amount')
                    ->label('الإجمالي')
                    ->numeric(),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'pending' => 'قيد الاعتماد',
                        'approved' => 'معتمد',
                        'sent' => 'مرسل للمورد',
                        'partial' => 'مستلم جزئياً',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ])
                    ->default('draft'),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2),
                Forms\Components\Textarea::make('terms_conditions')
                    ->label('الشروط والأحكام')
                    ->rows(2),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')->label('الرقم')->searchable(),
                Tables\Columns\TextColumn::make('po_date')->label('التاريخ')->date(),
                Tables\Columns\TextColumn::make('supplier.name_ar')->label('المورد')->searchable(),
                Tables\Columns\TextColumn::make('project.name_ar')->label('المشروع'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending' => 'warning',
                        'approved' => 'success',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'info',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'مسودة',
                        'approved' => 'معتمد',
                        'completed' => 'مكتمل',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
