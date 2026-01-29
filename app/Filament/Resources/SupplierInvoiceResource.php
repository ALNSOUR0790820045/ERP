<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierInvoiceResource\Pages;
use App\Models\SupplierInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierInvoiceResource extends Resource
{
    protected static ?string $model = SupplierInvoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'المشتريات والمخازن';
    protected static ?int $navigationSort = 10;

    public static function getModelLabel(): string
    {
        return 'فاتورة مورد';
    }

    public static function getPluralModelLabel(): string
    {
        return 'فواتير الموردين';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الفاتورة')->schema([
                Forms\Components\TextInput::make('invoice_number')
                    ->label('رقم الفاتورة')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('supplier_id')
                    ->label('المورد')
                    ->relationship('supplier', 'name_ar')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('purchase_order_id')
                    ->label('أمر الشراء')
                    ->relationship('purchaseOrder', 'order_number')
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'name_ar')
                    ->searchable()
                    ->preload(),
            ])->columns(2),

            Forms\Components\Section::make('التواريخ والشروط')->schema([
                Forms\Components\DatePicker::make('invoice_date')
                    ->label('تاريخ الفاتورة')
                    ->required()
                    ->default(now()),
                Forms\Components\DatePicker::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->required(),
                Forms\Components\TextInput::make('payment_terms')
                    ->label('شروط الدفع'),
            ])->columns(3),

            Forms\Components\Section::make('المبالغ')->schema([
                Forms\Components\TextInput::make('subtotal')
                    ->label('المجموع الفرعي')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('discount')
                    ->label('الخصم')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('vat_amount')
                    ->label('ضريبة القيمة المضافة')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('withholding_tax')
                    ->label('ضريبة الاستقطاع')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total_amount')
                    ->label('الإجمالي')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('paid_amount')
                    ->label('المدفوع')
                    ->numeric()
                    ->default(0),
                Forms\Components\Select::make('currency_id')
                    ->label('العملة')
                    ->relationship('currency', 'name_ar')
                    ->default(1),
                Forms\Components\TextInput::make('exchange_rate')
                    ->label('سعر الصرف')
                    ->numeric()
                    ->default(1),
            ])->columns(4),

            Forms\Components\Section::make('الحالة')->schema([
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'pending' => 'في الانتظار',
                        'approved' => 'معتمدة',
                        'paid' => 'مدفوعة',
                        'partially_paid' => 'مدفوعة جزئياً',
                        'cancelled' => 'ملغاة',
                    ])
                    ->default('draft'),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('رقم الفاتورة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name_ar')
                    ->label('المورد')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('تاريخ الفاتورة')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->money('JOD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('المدفوع')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('المتبقي')
                    ->money('JOD'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'pending',
                        'success' => 'approved',
                        'primary' => 'paid',
                        'info' => 'partially_paid',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'pending' => 'في الانتظار',
                        'approved' => 'معتمدة',
                        'paid' => 'مدفوعة',
                    ]),
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('المورد')
                    ->relationship('supplier', 'name_ar'),
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
            'index' => Pages\ListSupplierInvoices::route('/'),
            'create' => Pages\CreateSupplierInvoice::route('/create'),
            'edit' => Pages\EditSupplierInvoice::route('/{record}/edit'),
        ];
    }
}
