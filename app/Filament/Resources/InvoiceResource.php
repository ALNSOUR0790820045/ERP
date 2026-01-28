<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static ?string $navigationGroup = 'الفوترة';
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'فاتورة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الفواتير';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الفاتورة')->schema([
                Forms\Components\TextInput::make('invoice_number')
                    ->label('رقم الفاتورة')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\DatePicker::make('invoice_date')
                    ->label('تاريخ الفاتورة')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('invoice_type')
                    ->label('نوع الفاتورة')
                    ->options([
                        'progress' => 'مستخلص',
                        'advance' => 'دفعة مقدمة',
                        'final' => 'نهائية',
                        'retention' => 'ضمان',
                        'variation' => 'أعمال إضافية',
                    ])
                    ->required(),
                Forms\Components\Select::make('contract_id')
                    ->label('العقد')
                    ->relationship('contract', 'name')
                    ->searchable(),
                Forms\Components\Select::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'name_ar')
                    ->searchable(),
                Forms\Components\TextInput::make('period_number')
                    ->label('رقم الفترة')
                    ->numeric(),
                Forms\Components\DatePicker::make('period_from')
                    ->label('من تاريخ'),
                Forms\Components\DatePicker::make('period_to')
                    ->label('إلى تاريخ'),
            ])->columns(2),

            Forms\Components\Section::make('المبالغ')->schema([
                Forms\Components\TextInput::make('gross_amount')
                    ->label('المبلغ الإجمالي')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('discount_amount')
                    ->label('الخصم')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('retention_amount')
                    ->label('محجوز الضمان')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('advance_deduction')
                    ->label('خصم الدفعة المقدمة')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('tax_amount')
                    ->label('الضريبة')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('net_amount')
                    ->label('صافي المبلغ')
                    ->numeric()
                    ->disabled(),
            ])->columns(3),

            Forms\Components\Section::make('الحالة')->schema([
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'submitted' => 'مقدمة',
                        'under_review' => 'قيد المراجعة',
                        'approved' => 'معتمدة',
                        'partially_paid' => 'مدفوعة جزئياً',
                        'paid' => 'مدفوعة',
                        'rejected' => 'مرفوضة',
                    ])
                    ->default('draft'),
                Forms\Components\DatePicker::make('submitted_date')
                    ->label('تاريخ التقديم'),
                Forms\Components\DatePicker::make('approved_date')
                    ->label('تاريخ الاعتماد'),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2)
                    ->columnSpan(2),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->label('الرقم')->searchable(),
                Tables\Columns\TextColumn::make('invoice_date')->label('التاريخ')->date(),
                Tables\Columns\TextColumn::make('invoice_type')
                    ->label('النوع')
                    ->badge(),
                Tables\Columns\TextColumn::make('project.name_ar')->label('المشروع'),
                Tables\Columns\TextColumn::make('gross_amount')
                    ->label('المبلغ')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('net_amount')
                    ->label('الصافي')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'info',
                        'approved' => 'success',
                        'paid' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'مسودة',
                        'approved' => 'معتمدة',
                        'paid' => 'مدفوعة',
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
