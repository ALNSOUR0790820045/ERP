<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChequeBookResource\Pages;
use App\Models\FinanceAccounting\ChequeBook;
use App\Models\FinanceAccounting\BankAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChequeBookResource extends Resource
{
    protected static ?string $model = ChequeBook::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'المالية والمحاسبة';

    protected static ?string $navigationLabel = 'Cheque Books';

    protected static ?int $navigationSort = 60;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cheque Book Information')
                    ->schema([
                        Forms\Components\Select::make('bank_account_id')
                            ->label('Bank Account')
                            ->options(BankAccount::pluck('account_name', 'id'))
                            ->required()
                            ->searchable(),

                        Forms\Components\TextInput::make('book_number')
                            ->label('Book Number')
                            ->required()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('series_prefix')
                            ->label('Series Prefix')
                            ->maxLength(20)
                            ->helperText('Optional prefix for cheque numbers'),
                    ])->columns(3),

                Forms\Components\Section::make('Cheque Range')
                    ->schema([
                        Forms\Components\TextInput::make('start_number')
                            ->label('Start Number')
                            ->required()
                            ->numeric()
                            ->minValue(1),

                        Forms\Components\TextInput::make('end_number')
                            ->label('End Number')
                            ->required()
                            ->numeric()
                            ->minValue(1),

                        Forms\Components\TextInput::make('current_number')
                            ->label('Current Number')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('total_cheques')
                            ->label('Total Cheques')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(4),

                Forms\Components\Section::make('Dates & Status')
                    ->schema([
                        Forms\Components\DatePicker::make('received_date')
                            ->label('Received Date')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('expiry_date')
                            ->label('Expiry Date'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('active')
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('book_number')
                    ->label('Book Number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('bankAccount.account_name')
                    ->label('Bank Account')
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_number')
                    ->label('Start')
                    ->numeric(),

                Tables\Columns\TextColumn::make('end_number')
                    ->label('End')
                    ->numeric(),

                Tables\Columns\TextColumn::make('current_number')
                    ->label('Current')
                    ->numeric(),

                Tables\Columns\TextColumn::make('used_cheques')
                    ->label('Used')
                    ->numeric(),

                Tables\Columns\TextColumn::make('remaining_cheques')
                    ->label('Remaining')
                    ->numeric()
                    ->color(fn ($state) => $state <= 5 ? 'danger' : 'success'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'completed',
                        'danger' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('received_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('bank_account_id')
                    ->label('Bank Account')
                    ->options(BankAccount::pluck('account_name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListChequeBooks::route('/'),
            'create' => Pages\CreateChequeBook::route('/create'),
            'edit' => Pages\EditChequeBook::route('/{record}/edit'),
        ];
    }
}
