<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LetterOfCreditResource\Pages;
use App\Models\FinanceAccounting\LetterOfCredit;
use App\Models\FinanceAccounting\BankAccount;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class LetterOfCreditResource extends Resource
{
    protected static ?string $model = LetterOfCredit::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Finance & Accounting';

    protected static ?string $navigationLabel = 'Letters of Credit';

    protected static ?int $navigationSort = 64;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('lc_number')
                            ->label('LC Number')
                            ->default(fn () => LetterOfCredit::generateLcNumber())
                            ->required()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('lc_name')
                            ->label('LC Name / Description')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('lc_type')
                            ->label('LC Type')
                            ->options([
                                'sight' => 'Sight LC',
                                'usance' => 'Usance LC',
                                'deferred' => 'Deferred Payment',
                                'red_clause' => 'Red Clause',
                                'revolving' => 'Revolving',
                                'transferable' => 'Transferable',
                            ])
                            ->required(),

                        Forms\Components\Select::make('supplier_id')
                            ->label('Beneficiary (Supplier)')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Banks Information')
                    ->schema([
                        Forms\Components\Select::make('issuing_bank_id')
                            ->label('Issuing Bank Account')
                            ->options(BankAccount::pluck('account_name', 'id'))
                            ->searchable(),

                        Forms\Components\TextInput::make('issuing_bank_name')
                            ->label('Issuing Bank Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('advising_bank')
                            ->label('Advising Bank')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('confirming_bank')
                            ->label('Confirming Bank')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('beneficiary_name')
                            ->label('Beneficiary Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('beneficiary_bank')
                            ->label('Beneficiary Bank')
                            ->maxLength(255),
                    ])->columns(3),

                Forms\Components\Section::make('Dates & Amount')
                    ->schema([
                        Forms\Components\DatePicker::make('issue_date')
                            ->label('Issue Date')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('expiry_date')
                            ->label('Expiry Date')
                            ->required(),

                        Forms\Components\DatePicker::make('latest_shipment_date')
                            ->label('Latest Shipment Date'),

                        Forms\Components\TextInput::make('lc_amount')
                            ->label('LC Amount')
                            ->required()
                            ->numeric()
                            ->prefix('JOD'),

                        Forms\Components\TextInput::make('tolerance_percentage')
                            ->label('Tolerance %')
                            ->numeric()
                            ->default(0)
                            ->suffix('%'),

                        Forms\Components\TextInput::make('margin_amount')
                            ->label('Margin Amount')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD'),

                        Forms\Components\TextInput::make('commission_amount')
                            ->label('Commission')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD'),
                    ])->columns(4),

                Forms\Components\Section::make('Terms & Conditions')
                    ->schema([
                        Forms\Components\Toggle::make('is_confirmed')
                            ->label('Confirmed LC'),

                        Forms\Components\Toggle::make('is_transferable')
                            ->label('Transferable'),

                        Forms\Components\Toggle::make('partial_shipment_allowed')
                            ->label('Partial Shipment Allowed')
                            ->default(true),

                        Forms\Components\Toggle::make('transhipment_allowed')
                            ->label('Transhipment Allowed')
                            ->default(true),
                    ])->columns(4),

                Forms\Components\Section::make('Shipment Details')
                    ->schema([
                        Forms\Components\Textarea::make('goods_description')
                            ->label('Goods Description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('port_of_loading')
                            ->label('Port of Loading')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('port_of_discharge')
                            ->label('Port of Discharge')
                            ->maxLength(255),

                        Forms\Components\Select::make('incoterms')
                            ->label('Incoterms')
                            ->options([
                                'EXW' => 'EXW - Ex Works',
                                'FCA' => 'FCA - Free Carrier',
                                'CPT' => 'CPT - Carriage Paid To',
                                'CIP' => 'CIP - Carriage and Insurance Paid To',
                                'DAP' => 'DAP - Delivered at Place',
                                'DPU' => 'DPU - Delivered at Place Unloaded',
                                'DDP' => 'DDP - Delivered Duty Paid',
                                'FAS' => 'FAS - Free Alongside Ship',
                                'FOB' => 'FOB - Free on Board',
                                'CFR' => 'CFR - Cost and Freight',
                                'CIF' => 'CIF - Cost, Insurance and Freight',
                            ]),
                    ])->columns(3),

                Forms\Components\Section::make('Documents Required')
                    ->schema([
                        Forms\Components\TagsInput::make('required_documents')
                            ->label('Required Documents')
                            ->placeholder('Add required document')
                            ->suggestions([
                                'Commercial Invoice',
                                'Packing List',
                                'Bill of Lading',
                                'Certificate of Origin',
                                'Insurance Certificate',
                                'Quality Certificate',
                                'Weight Certificate',
                            ]),

                        Forms\Components\Textarea::make('terms_and_conditions')
                            ->label('Additional Terms & Conditions')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lc_number')
                    ->label('LC #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('lc_name')
                    ->label('Description')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Beneficiary')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('lc_type')
                    ->label('Type')
                    ->badge(),

                Tables\Columns\TextColumn::make('lc_amount')
                    ->label('Amount')
                    ->money('JOD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('utilized_amount')
                    ->label('Utilized')
                    ->money('JOD'),

                Tables\Columns\TextColumn::make('available_amount')
                    ->label('Available')
                    ->money('JOD'),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Expiry')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->days_until_expiry < 30 ? 'danger' : null),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'requested',
                        'success' => 'issued',
                        'warning' => 'amended',
                        'primary' => 'utilized',
                        'secondary' => 'closed',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'issued' => 'Issued',
                        'amended' => 'Amended',
                        'utilized' => 'Fully Utilized',
                        'closed' => 'Closed',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('lc_type')
                    ->options([
                        'sight' => 'Sight LC',
                        'usance' => 'Usance LC',
                        'deferred' => 'Deferred Payment',
                    ]),

                Tables\Filters\Filter::make('expiring_soon')
                    ->query(fn ($query) => $query->where('expiry_date', '<=', now()->addDays(30))
                        ->where('expiry_date', '>=', now())
                        ->whereIn('status', ['issued', 'amended'])),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn ($record) => in_array($record->status, ['draft', 'issued'])),

                    Tables\Actions\Action::make('amend')
                        ->label('Create Amendment')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->visible(fn ($record) => in_array($record->status, ['issued', 'amended']))
                        ->form([
                            Forms\Components\Select::make('amendment_type')
                                ->options([
                                    'amount' => 'Amount Change',
                                    'expiry' => 'Expiry Date',
                                    'shipment_date' => 'Shipment Date',
                                    'terms' => 'Terms & Conditions',
                                    'documents' => 'Documents',
                                    'other' => 'Other',
                                ])
                                ->required(),

                            Forms\Components\TextInput::make('amount_change')
                                ->numeric()
                                ->prefix('JOD'),

                            Forms\Components\DatePicker::make('new_expiry_date'),

                            Forms\Components\Textarea::make('description')
                                ->required(),

                            Forms\Components\TextInput::make('amendment_fees')
                                ->numeric()
                                ->default(0),
                        ])
                        ->action(function ($record, array $data) {
                            $record->amendments()->create([
                                'amendment_number' => $record->lc_number . '-A' . ($record->amendments()->count() + 1),
                                'amendment_date' => now(),
                                'amendment_type' => $data['amendment_type'],
                                'description' => $data['description'],
                                'amount_change' => $data['amount_change'],
                                'new_expiry_date' => $data['new_expiry_date'],
                                'amendment_fees' => $data['amendment_fees'],
                                'status' => 'pending',
                                'created_by' => auth()->id(),
                            ]);

                            Notification::make()
                                ->title('Amendment created')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('utilize')
                        ->label('Record Utilization')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->visible(fn ($record) => in_array($record->status, ['issued', 'amended']) && $record->available_amount > 0)
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->label('Utilization Amount')
                                ->numeric()
                                ->required()
                                ->prefix('JOD'),

                            Forms\Components\TextInput::make('shipment_reference')
                                ->label('Shipment Reference'),

                            Forms\Components\DatePicker::make('shipment_date'),
                        ])
                        ->action(function ($record, array $data) {
                            if ($data['amount'] > $record->available_amount) {
                                Notification::make()
                                    ->title('Amount exceeds available balance')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $record->utilizations()->create([
                                'utilization_number' => $record->lc_number . '-U' . ($record->utilizations()->count() + 1),
                                'utilization_date' => now(),
                                'amount' => $data['amount'],
                                'shipment_reference' => $data['shipment_reference'],
                                'shipment_date' => $data['shipment_date'],
                                'status' => 'pending',
                            ]);

                            $record->updateAvailableAmount();

                            Notification::make()
                                ->title('Utilization recorded')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('close')
                        ->label('Close LC')
                        ->icon('heroicon-o-x-mark')
                        ->color('secondary')
                        ->visible(fn ($record) => in_array($record->status, ['issued', 'amended', 'utilized']))
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['status' => 'closed']);
                            Notification::make()
                                ->title('LC closed')
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
            'index' => Pages\ListLettersOfCredit::route('/'),
            'create' => Pages\CreateLetterOfCredit::route('/create'),
            'edit' => Pages\EditLetterOfCredit::route('/{record}/edit'),
        ];
    }
}
