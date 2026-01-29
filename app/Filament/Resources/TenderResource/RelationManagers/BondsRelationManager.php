<?php

namespace App\Filament\Resources\TenderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Enums\BondType;

class BondsRelationManager extends RelationManager
{
    protected static string $relationship = 'bonds';

    protected static ?string $title = 'الكفالات';
    
    protected static ?string $modelLabel = 'كفالة';
    
    protected static ?string $pluralModelLabel = 'الكفالات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('bond_type')
                    ->label('نوع الكفالة')
                    ->options([
                        'bid' => 'كفالة العطاء (ابتدائية)',
                        'performance' => 'كفالة حسن التنفيذ',
                        'advance_payment' => 'كفالة الدفعة المقدمة',
                        'retention' => 'كفالة المحتجزات',
                    ])
                    ->required(),
                Forms\Components\Select::make('bank_id')
                    ->label('البنك')
                    ->relationship('bank', 'name_ar')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('bond_number')
                    ->label('رقم الكفالة')
                    ->maxLength(100),
                Forms\Components\TextInput::make('amount')
                    ->label('المبلغ')
                    ->numeric()
                    ->prefix('د.أ')
                    ->required(),
                Forms\Components\DatePicker::make('issue_date')
                    ->label('تاريخ الإصدار')
                    ->required(),
                Forms\Components\DatePicker::make('expiry_date')
                    ->label('تاريخ الانتهاء')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'active' => 'فعالة',
                        'expired' => 'منتهية',
                        'released' => 'محررة',
                        'claimed' => 'مطالب بها',
                    ])
                    ->default('draft'),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('bond_number')
            ->columns([
                Tables\Columns\TextColumn::make('bond_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'bid' => 'ابتدائية',
                        'performance' => 'حسن التنفيذ',
                        'advance_payment' => 'دفعة مقدمة',
                        'retention' => 'محتجزات',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'bid' => 'info',
                        'performance' => 'success',
                        'advance_payment' => 'warning',
                        'retention' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('bond_number')
                    ->label('رقم الكفالة')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('JOD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('تاريخ الانتهاء')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->expiry_date < now() ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'مسودة',
                        'active' => 'فعالة',
                        'expired' => 'منتهية',
                        'released' => 'محررة',
                        'claimed' => 'مطالب بها',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'released' => 'info',
                        default => 'gray',
                    }),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
