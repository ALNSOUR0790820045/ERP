<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncomeTaxResource\Pages;
use App\Models\IncomeTax;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IncomeTaxResource extends Resource
{
    protected static ?string $model = IncomeTax::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    
    protected static ?string $navigationGroup = 'الموارد البشرية';
    
    protected static ?string $modelLabel = 'ضريبة دخل';
    
    protected static ?string $pluralModelLabel = 'ضرائب الدخل';
    
    protected static ?int $navigationSort = 25;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الضريبة')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('الموظف')
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->required(),
                            
                        Forms\Components\TextInput::make('year')
                            ->label('السنة')
                            ->numeric()
                            ->required()
                            ->default(date('Y')),
                            
                        Forms\Components\TextInput::make('month')
                            ->label('الشهر')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(12)
                            ->required(),
                    ])->columns(3),
                    
                Forms\Components\Section::make('تفاصيل الدخل')
                    ->schema([
                        Forms\Components\TextInput::make('gross_salary')
                            ->label('الراتب الإجمالي')
                            ->numeric()
                            ->prefix('د.أ')
                            ->required(),
                            
                        Forms\Components\TextInput::make('allowances')
                            ->label('البدلات الخاضعة')
                            ->numeric()
                            ->prefix('د.أ')
                            ->default(0),
                            
                        Forms\Components\TextInput::make('deductions')
                            ->label('الخصومات المعفاة')
                            ->numeric()
                            ->prefix('د.أ')
                            ->default(0),
                            
                        Forms\Components\TextInput::make('taxable_income')
                            ->label('الدخل الخاضع للضريبة')
                            ->numeric()
                            ->prefix('د.أ')
                            ->required(),
                    ])->columns(4),
                    
                Forms\Components\Section::make('حساب الضريبة')
                    ->schema([
                        Forms\Components\TextInput::make('tax_amount')
                            ->label('مبلغ الضريبة')
                            ->numeric()
                            ->prefix('د.أ')
                            ->required(),
                            
                        Forms\Components\TextInput::make('tax_rate')
                            ->label('معدل الضريبة %')
                            ->numeric()
                            ->suffix('%'),
                            
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'calculated' => 'محسوب',
                                'deducted' => 'مستقطع',
                                'paid' => 'مدفوع',
                            ])
                            ->default('calculated')
                            ->required(),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('الموظف')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('year')
                    ->label('السنة')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('month')
                    ->label('الشهر')
                    ->formatStateUsing(fn ($state) => [
                        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
                        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
                        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
                    ][$state] ?? $state)
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('gross_salary')
                    ->label('الراتب الإجمالي')
                    ->money('JOD')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('taxable_income')
                    ->label('الدخل الخاضع')
                    ->money('JOD')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('tax_amount')
                    ->label('الضريبة')
                    ->money('JOD')
                    ->sortable()
                    ->color('danger'),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'calculated' => 'محسوب',
                        'deducted' => 'مستقطع',
                        'paid' => 'مدفوع',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'calculated' => 'gray',
                        'deducted' => 'warning',
                        'paid' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->label('السنة')
                    ->options(fn () => collect(range(date('Y') - 5, date('Y')))->mapWithKeys(fn ($y) => [$y => $y])),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'calculated' => 'محسوب',
                        'deducted' => 'مستقطع',
                        'paid' => 'مدفوع',
                    ]),
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
            'index' => Pages\ListIncomeTaxes::route('/'),
            'create' => Pages\CreateIncomeTax::route('/create'),
            'edit' => Pages\EditIncomeTax::route('/{record}/edit'),
        ];
    }
}
