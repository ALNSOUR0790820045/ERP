<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaseResource\Pages;
use App\Models\FinanceAccounting\Lease;
use App\Models\FinanceAccounting\ChartOfAccount;
use App\Models\Supplier;
use App\Services\FinanceAccounting\LeaseAccountingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class LeaseResource extends Resource
{
    protected static ?string $model = Lease::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Finance & Accounting';

    protected static ?string $navigationLabel = 'Leases (IFRS 16)';

    protected static ?int $navigationSort = 65;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('lease_number')
                            ->label('Lease Number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        Forms\Components\TextInput::make('lease_name')
                            ->label('Lease Name / Description')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('lease_type')
                            ->label('Lease Type')
                            ->options([
                                'finance' => 'Finance Lease',
                                'operating' => 'Operating Lease',
                                'short_term' => 'Short-Term (< 12 months)',
                                'low_value' => 'Low Value Asset',
                            ])
                            ->required(),

                        Forms\Components\Select::make('asset_type')
                            ->label('Asset Type')
                            ->options([
                                'building' => 'Building / Property',
                                'vehicle' => 'Vehicle',
                                'equipment' => 'Equipment',
                                'land' => 'Land',
                                'other' => 'Other',
                            ])
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Lessor & Asset Details')
                    ->schema([
                        Forms\Components\Select::make('lessor_id')
                            ->label('Lessor (Supplier)')
                            ->relationship('lessor', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Textarea::make('asset_description')
                            ->label('Asset Description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Lease Terms')
                    ->schema([
                        Forms\Components\DatePicker::make('commencement_date')
                            ->label('Commencement Date')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state && $get('lease_term_months')) {
                                    $endDate = \Carbon\Carbon::parse($state)->addMonths($get('lease_term_months'));
                                    $set('end_date', $endDate->format('Y-m-d'));
                                }
                            }),

                        Forms\Components\TextInput::make('lease_term_months')
                            ->label('Lease Term (Months)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state && $get('commencement_date')) {
                                    $endDate = \Carbon\Carbon::parse($get('commencement_date'))->addMonths($state);
                                    $set('end_date', $endDate->format('Y-m-d'));
                                }
                            }),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->required(),

                        Forms\Components\TextInput::make('monthly_payment')
                            ->label('Payment Amount')
                            ->required()
                            ->numeric()
                            ->prefix('JOD'),

                        Forms\Components\Select::make('payment_frequency')
                            ->label('Payment Frequency')
                            ->options([
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'semi_annual' => 'Semi-Annual',
                                'annual' => 'Annual',
                            ])
                            ->default('monthly')
                            ->required(),

                        Forms\Components\Select::make('payment_timing')
                            ->label('Payment Timing')
                            ->options([
                                'beginning' => 'Beginning of Period',
                                'end' => 'End of Period',
                            ])
                            ->default('end')
                            ->required(),

                        Forms\Components\TextInput::make('incremental_borrowing_rate')
                            ->label('Incremental Borrowing Rate (%)')
                            ->required()
                            ->numeric()
                            ->suffix('%')
                            ->default(5),
                    ])->columns(4),

                Forms\Components\Section::make('Initial Recognition Adjustments')
                    ->schema([
                        Forms\Components\TextInput::make('initial_direct_costs')
                            ->label('Initial Direct Costs')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD'),

                        Forms\Components\TextInput::make('lease_incentives')
                            ->label('Lease Incentives Received')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD'),

                        Forms\Components\TextInput::make('restoration_costs')
                            ->label('Restoration/Dismantling Costs')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD'),
                    ])->columns(3),

                Forms\Components\Section::make('Options')
                    ->schema([
                        Forms\Components\Toggle::make('has_purchase_option')
                            ->label('Has Purchase Option')
                            ->reactive(),

                        Forms\Components\TextInput::make('purchase_option_price')
                            ->label('Purchase Option Price')
                            ->numeric()
                            ->prefix('JOD')
                            ->visible(fn ($get) => $get('has_purchase_option')),

                        Forms\Components\Toggle::make('has_extension_option')
                            ->label('Has Extension Option')
                            ->reactive(),

                        Forms\Components\TextInput::make('extension_period_months')
                            ->label('Extension Period (Months)')
                            ->numeric()
                            ->visible(fn ($get) => $get('has_extension_option')),

                        Forms\Components\Toggle::make('has_termination_option')
                            ->label('Has Termination Option'),
                    ])->columns(5),

                Forms\Components\Section::make('Accounting Configuration')
                    ->schema([
                        Forms\Components\Select::make('rou_asset_account_id')
                            ->label('ROU Asset Account')
                            ->options(ChartOfAccount::where('type', 'asset')->pluck('name', 'id'))
                            ->searchable(),

                        Forms\Components\Select::make('lease_liability_account_id')
                            ->label('Lease Liability Account')
                            ->options(ChartOfAccount::where('type', 'liability')->pluck('name', 'id'))
                            ->searchable(),

                        Forms\Components\Select::make('depreciation_expense_account_id')
                            ->label('Depreciation Expense Account')
                            ->options(ChartOfAccount::where('type', 'expense')->pluck('name', 'id'))
                            ->searchable(),

                        Forms\Components\Select::make('interest_expense_account_id')
                            ->label('Interest Expense Account')
                            ->options(ChartOfAccount::where('type', 'expense')->pluck('name', 'id'))
                            ->searchable(),
                    ])->columns(2),

                Forms\Components\Section::make('Calculated Values')
                    ->schema([
                        Forms\Components\TextInput::make('right_of_use_asset')
                            ->label('Right-of-Use Asset')
                            ->numeric()
                            ->disabled()
                            ->prefix('JOD'),

                        Forms\Components\TextInput::make('lease_liability')
                            ->label('Lease Liability')
                            ->numeric()
                            ->disabled()
                            ->prefix('JOD'),

                        Forms\Components\TextInput::make('accumulated_depreciation')
                            ->label('Accumulated Depreciation')
                            ->numeric()
                            ->disabled()
                            ->prefix('JOD'),
                    ])->columns(3)
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lease_number')
                    ->label('Lease #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('lease_name')
                    ->label('Description')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('lease_type')
                    ->label('Type')
                    ->badge()
                    ->colors([
                        'primary' => 'finance',
                        'success' => 'operating',
                        'warning' => 'short_term',
                        'secondary' => 'low_value',
                    ]),

                Tables\Columns\TextColumn::make('asset_type')
                    ->label('Asset')
                    ->badge(),

                Tables\Columns\TextColumn::make('monthly_payment')
                    ->label('Payment')
                    ->money('JOD'),

                Tables\Columns\TextColumn::make('right_of_use_asset')
                    ->label('ROU Asset')
                    ->money('JOD'),

                Tables\Columns\TextColumn::make('net_book_value')
                    ->label('Net Book Value')
                    ->money('JOD'),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('End Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'active',
                        'warning' => 'modified',
                        'danger' => 'terminated',
                        'secondary' => 'expired',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'modified' => 'Modified',
                        'terminated' => 'Terminated',
                        'expired' => 'Expired',
                    ]),

                Tables\Filters\SelectFilter::make('lease_type')
                    ->options([
                        'finance' => 'Finance Lease',
                        'operating' => 'Operating Lease',
                        'short_term' => 'Short-Term',
                        'low_value' => 'Low Value',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn ($record) => $record->status === 'draft'),

                    Tables\Actions\Action::make('activate')
                        ->label('Activate & Calculate')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'draft')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $service = app(LeaseAccountingService::class);
                            
                            // Calculate initial recognition
                            $recognition = $service->calculateInitialRecognition($record);
                            
                            $record->update([
                                'right_of_use_asset' => $recognition['right_of_use_asset'],
                                'lease_liability' => $recognition['lease_liability'],
                                'status' => 'active',
                            ]);

                            // Generate payment schedule
                            $service->createPaymentScheduleRecords($record);

                            Notification::make()
                                ->title('Lease activated successfully')
                                ->body("ROU Asset: " . number_format($recognition['right_of_use_asset'], 2) . 
                                       " | Liability: " . number_format($recognition['lease_liability'], 2))
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('depreciate')
                        ->label('Record Depreciation')
                        ->icon('heroicon-o-minus-circle')
                        ->color('warning')
                        ->visible(fn ($record) => $record->status === 'active')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $service = app(LeaseAccountingService::class);
                            $depreciation = $service->recordDepreciation($record);

                            if ($depreciation) {
                                Notification::make()
                                    ->title('Depreciation recorded')
                                    ->body("Amount: " . number_format($depreciation->depreciation_amount, 2))
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Could not record depreciation')
                                    ->body('Please configure depreciation expense account')
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('schedule')
                        ->label('View Schedule')
                        ->icon('heroicon-o-calendar')
                        ->color('info')
                        ->visible(fn ($record) => $record->status === 'active')
                        ->url(fn ($record) => route('filament.admin.resources.leases.schedule', $record)),
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
            'index' => Pages\ListLeases::route('/'),
            'create' => Pages\CreateLease::route('/create'),
            'edit' => Pages\EditLease::route('/{record}/edit'),
        ];
    }
}
