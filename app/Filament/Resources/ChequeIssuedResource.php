<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChequeIssuedResource\Pages;
use App\Models\FinanceAccounting\ChequeIssued;
use App\Models\FinanceAccounting\ChequeBook;
use App\Models\FinanceAccounting\BankAccount;
use App\Services\FinanceAccounting\ChequeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ChequeIssuedResource extends Resource
{
    protected static ?string $model = ChequeIssued::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Finance & Accounting';

    protected static ?string $navigationLabel = 'Cheques Issued';

    protected static ?int $navigationSort = 61;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cheque Information')
                    ->schema([
                        Forms\Components\Select::make('cheque_book_id')
                            ->label('Cheque Book')
                            ->options(ChequeBook::where('status', 'active')->pluck('book_number', 'id'))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $chequeBook = ChequeBook::find($state);
                                    $set('bank_account_id', $chequeBook->bank_account_id);
                                    $set('cheque_number', $chequeBook->getNextChequeNumber());
                                }
                            }),

                        Forms\Components\TextInput::make('cheque_number')
                            ->label('Cheque Number')
                            ->required()
                            ->maxLength(50),

                        Forms\Components\Hidden::make('bank_account_id'),

                        Forms\Components\DatePicker::make('cheque_date')
                            ->label('Cheque Date')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')
                            ->default(now()),
                    ])->columns(4),

                Forms\Components\Section::make('Payee & Amount')
                    ->schema([
                        Forms\Components\TextInput::make('payee_name')
                            ->label('Payee Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('payee_type')
                            ->options([
                                'supplier' => 'Supplier',
                                'employee' => 'Employee',
                                'other' => 'Other',
                            ])
                            ->default('supplier'),

                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->required()
                            ->numeric()
                            ->prefix('JOD')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('amount_in_words', ChequeIssued::convertToWords((float) $state));
                                }
                            }),

                        Forms\Components\TextInput::make('amount_in_words')
                            ->label('Amount in Words')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('Additional Details')
                    ->schema([
                        Forms\Components\Textarea::make('memo')
                            ->label('Memo / Purpose')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'printed' => 'Printed',
                                'issued' => 'Issued',
                                'cleared' => 'Cleared',
                                'bounced' => 'Bounced',
                                'cancelled' => 'Cancelled',
                                'stopped' => 'Stopped',
                            ])
                            ->default('draft')
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

                Tables\Columns\TextColumn::make('chequeBook.book_number')
                    ->label('Book'),

                Tables\Columns\TextColumn::make('cheque_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('payee_name')
                    ->label('Payee')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('JOD')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'printed',
                        'warning' => 'issued',
                        'success' => 'cleared',
                        'danger' => fn ($state) => in_array($state, ['bounced', 'cancelled', 'stopped']),
                    ]),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_overdue')
                    ->label('Overdue')
                    ->boolean()
                    ->trueColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'printed' => 'Printed',
                        'issued' => 'Issued',
                        'cleared' => 'Cleared',
                        'bounced' => 'Bounced',
                        'cancelled' => 'Cancelled',
                        'stopped' => 'Stopped',
                    ]),

                Tables\Filters\Filter::make('overdue')
                    ->query(fn ($query) => $query->whereIn('status', ['issued', 'printed'])
                        ->where('due_date', '<', now())),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn ($record) => $record->status === 'draft'),
                    
                    Tables\Actions\Action::make('print')
                        ->label('Print')
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->visible(fn ($record) => in_array($record->status, ['draft', 'printed']))
                        ->action(function ($record) {
                            $record->recordPrint(auth()->id());
                            Notification::make()
                                ->title('Cheque marked as printed')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('issue')
                        ->label('Mark as Issued')
                        ->icon('heroicon-o-check-circle')
                        ->color('warning')
                        ->visible(fn ($record) => in_array($record->status, ['draft', 'printed']))
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->markAsIssued();
                            Notification::make()
                                ->title('Cheque marked as issued')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('clear')
                        ->label('Mark as Cleared')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn ($record) => in_array($record->status, ['issued', 'printed']))
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->markAsCleared();
                            Notification::make()
                                ->title('Cheque marked as cleared')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('bounce')
                        ->label('Mark as Bounced')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => in_array($record->status, ['issued', 'printed']))
                        ->form([
                            Forms\Components\Textarea::make('bounce_reason')
                                ->label('Bounce Reason')
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $record->markAsBounced($data['bounce_reason']);
                            Notification::make()
                                ->title('Cheque marked as bounced')
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\Action::make('cancel')
                        ->label('Cancel')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn ($record) => !in_array($record->status, ['cleared', 'cancelled']))
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->markAsCancelled();
                            Notification::make()
                                ->title('Cheque cancelled')
                                ->warning()
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
            'index' => Pages\ListChequesIssued::route('/'),
            'create' => Pages\CreateChequeIssued::route('/create'),
            'edit' => Pages\EditChequeIssued::route('/{record}/edit'),
        ];
    }
}
