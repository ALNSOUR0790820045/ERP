<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChequeReceivedResource\Pages;
use App\Models\FinanceAccounting\ChequeReceived;
use App\Models\FinanceAccounting\BankAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ChequeReceivedResource extends Resource
{
    protected static ?string $model = ChequeReceived::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static ?string $navigationGroup = 'Finance & Accounting';

    protected static ?string $navigationLabel = 'Cheques Received';

    protected static ?int $navigationSort = 62;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cheque Information')
                    ->schema([
                        Forms\Components\TextInput::make('cheque_number')
                            ->label('Cheque Number')
                            ->required()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('branch_name')
                            ->label('Branch Name')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('drawer_account_number')
                            ->label('Drawer Account Number')
                            ->maxLength(50),
                    ])->columns(4),

                Forms\Components\Section::make('Dates & Amount')
                    ->schema([
                        Forms\Components\DatePicker::make('cheque_date')
                            ->label('Cheque Date')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')
                            ->default(now()),

                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->required()
                            ->numeric()
                            ->prefix('JOD'),
                    ])->columns(3),

                Forms\Components\Section::make('Drawer Information')
                    ->schema([
                        Forms\Components\TextInput::make('drawer_name')
                            ->label('Drawer Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('drawer_type')
                            ->options([
                                'customer' => 'Customer',
                                'other' => 'Other',
                            ])
                            ->default('customer'),

                        Forms\Components\Textarea::make('memo')
                            ->label('Memo / Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'received' => 'Received',
                                'under_collection' => 'Under Collection',
                                'deposited' => 'Deposited',
                                'collected' => 'Collected',
                                'returned' => 'Returned',
                                'endorsed' => 'Endorsed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('received')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cheque_number')
                    ->label('Cheque #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('bank_name')
                    ->label('Bank')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('drawer_name')
                    ->label('Drawer')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('cheque_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('JOD')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'received',
                        'info' => 'under_collection',
                        'warning' => 'deposited',
                        'success' => 'collected',
                        'danger' => fn ($state) => in_array($state, ['returned', 'cancelled']),
                        'primary' => 'endorsed',
                    ]),

                Tables\Columns\IconColumn::make('is_matured')
                    ->label('Matured')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'received' => 'Received',
                        'under_collection' => 'Under Collection',
                        'deposited' => 'Deposited',
                        'collected' => 'Collected',
                        'returned' => 'Returned',
                        'endorsed' => 'Endorsed',
                    ]),

                Tables\Filters\Filter::make('matured')
                    ->query(fn ($query) => $query->where('due_date', '<=', now())),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn ($record) => $record->status === 'received'),

                    Tables\Actions\Action::make('deposit')
                        ->label('Deposit to Bank')
                        ->icon('heroicon-o-building-library')
                        ->color('info')
                        ->visible(fn ($record) => $record->status === 'received')
                        ->form([
                            Forms\Components\Select::make('bank_account_id')
                                ->label('Deposit to Bank Account')
                                ->options(BankAccount::pluck('account_name', 'id'))
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $record->markAsDeposited($data['bank_account_id']);
                            Notification::make()
                                ->title('Cheque deposited successfully')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('collect')
                        ->label('Mark as Collected')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn ($record) => in_array($record->status, ['deposited', 'under_collection']))
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->markAsCollected();
                            Notification::make()
                                ->title('Cheque collected successfully')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('return')
                        ->label('Mark as Returned')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('danger')
                        ->visible(fn ($record) => in_array($record->status, ['deposited', 'under_collection']))
                        ->form([
                            Forms\Components\Textarea::make('return_reason')
                                ->label('Return Reason')
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $record->markAsReturned($data['return_reason']);
                            Notification::make()
                                ->title('Cheque marked as returned')
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\Action::make('endorse')
                        ->label('Endorse')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('primary')
                        ->visible(fn ($record) => $record->status === 'received')
                        ->form([
                            Forms\Components\TextInput::make('endorsed_to')
                                ->label('Endorse To (Supplier ID)')
                                ->required()
                                ->numeric(),
                        ])
                        ->action(function ($record, array $data) {
                            $record->endorse($data['endorsed_to']);
                            Notification::make()
                                ->title('Cheque endorsed successfully')
                                ->success()
                                ->send();
                        }),
                ]),
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
            'index' => Pages\ListChequesReceived::route('/'),
            'create' => Pages\CreateChequeReceived::route('/create'),
            'edit' => Pages\EditChequeReceived::route('/{record}/edit'),
        ];
    }
}
