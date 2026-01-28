<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanResource\Pages;
use App\Models\Loan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'الموارد البشرية';
    protected static ?int $navigationSort = 15;

    public static function getModelLabel(): string
    {
        return 'قرض';
    }

    public static function getPluralModelLabel(): string
    {
        return 'القروض';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات القرض')->schema([
                Forms\Components\TextInput::make('loan_number')
                    ->label('رقم القرض')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn() => 'LN-' . date('Y') . '-' . str_pad(Loan::count() + 1, 4, '0', STR_PAD_LEFT)),
                Forms\Components\Select::make('employee_id')
                    ->label('الموظف')
                    ->relationship('employee', 'name_ar')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('loan_type')
                    ->label('نوع القرض')
                    ->options([
                        'personal' => 'قرض شخصي',
                        'emergency' => 'قرض طوارئ',
                        'housing' => 'قرض سكني',
                        'car' => 'قرض سيارة',
                        'education' => 'قرض تعليمي',
                        'other' => 'أخرى',
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('loan_date')
                    ->label('تاريخ القرض')
                    ->required()
                    ->default(now()),
            ])->columns(2),

            Forms\Components\Section::make('المبالغ والأقساط')->schema([
                Forms\Components\TextInput::make('principal_amount')
                    ->label('المبلغ الأساسي')
                    ->numeric()
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        $interest = $state * ($get('interest_rate') / 100);
                        $set('total_amount', $state + $interest);
                        if ($get('number_of_installments') > 0) {
                            $set('installment_amount', ($state + $interest) / $get('number_of_installments'));
                        }
                    }),
                Forms\Components\TextInput::make('interest_rate')
                    ->label('نسبة الفائدة %')
                    ->numeric()
                    ->default(0)
                    ->live(onBlur: true),
                Forms\Components\TextInput::make('total_amount')
                    ->label('المبلغ الإجمالي')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('number_of_installments')
                    ->label('عدد الأقساط')
                    ->numeric()
                    ->required()
                    ->live(onBlur: true),
                Forms\Components\TextInput::make('installment_amount')
                    ->label('قيمة القسط')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('remaining_balance')
                    ->label('الرصيد المتبقي')
                    ->numeric()
                    ->default(fn ($get) => $get('total_amount')),
            ])->columns(3),

            Forms\Components\Section::make('التواريخ')->schema([
                Forms\Components\DatePicker::make('start_date')
                    ->label('تاريخ البدء')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->label('تاريخ الانتهاء'),
            ])->columns(2),

            Forms\Components\Section::make('الحالة')->schema([
                Forms\Components\Textarea::make('purpose')
                    ->label('الغرض من القرض')
                    ->rows(2),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'في الانتظار',
                        'approved' => 'معتمد',
                        'active' => 'نشط',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ])
                    ->default('pending'),
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
                Tables\Columns\TextColumn::make('loan_number')
                    ->label('رقم القرض')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.name_ar')
                    ->label('الموظف')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan_type')
                    ->label('النوع')
                    ->badge(),
                Tables\Columns\TextColumn::make('principal_amount')
                    ->label('المبلغ الأساسي')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('remaining_balance')
                    ->label('المتبقي')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('number_of_installments')
                    ->label('الأقساط'),
                Tables\Columns\TextColumn::make('paid_installments')
                    ->label('المدفوعة'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'approved',
                        'success' => 'active',
                        'primary' => 'completed',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'في الانتظار',
                        'approved' => 'معتمد',
                        'active' => 'نشط',
                        'completed' => 'مكتمل',
                    ]),
                Tables\Filters\SelectFilter::make('loan_type')
                    ->label('النوع')
                    ->options([
                        'personal' => 'قرض شخصي',
                        'emergency' => 'قرض طوارئ',
                        'housing' => 'قرض سكني',
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
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoan::route('/create'),
            'edit' => Pages\EditLoan::route('/{record}/edit'),
        ];
    }
}
