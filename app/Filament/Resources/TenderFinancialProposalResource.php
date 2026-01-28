<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderFinancialProposalResource\Pages;
use App\Models\TenderFinancialProposal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderFinancialProposalResource extends Resource
{
    protected static ?string $model = TenderFinancialProposal::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    
    protected static ?string $navigationGroup = 'إدارة العطاءات';
    
    protected static ?string $modelLabel = 'عرض مالي';
    
    protected static ?string $pluralModelLabel = 'العروض المالية';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات العرض المالي')
                    ->schema([
                        Forms\Components\Select::make('tender_id')
                            ->label('العطاء')
                            ->relationship('tender', 'title')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\Select::make('vendor_id')
                            ->label('المورد/المقاول')
                            ->relationship('vendor', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('المبالغ')
                    ->schema([
                        Forms\Components\TextInput::make('total_amount')
                            ->label('المبلغ الإجمالي')
                            ->numeric()
                            ->prefix('د.أ')
                            ->required(),
                        
                        Forms\Components\TextInput::make('discount_percentage')
                            ->label('نسبة الخصم')
                            ->numeric()
                            ->suffix('%')
                            ->default(0),
                        
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('مبلغ الخصم')
                            ->numeric()
                            ->prefix('د.أ')
                            ->default(0),
                        
                        Forms\Components\TextInput::make('net_amount')
                            ->label('المبلغ الصافي')
                            ->numeric()
                            ->prefix('د.أ'),
                        
                        Forms\Components\TextInput::make('vat_amount')
                            ->label('ضريبة المبيعات')
                            ->numeric()
                            ->prefix('د.أ')
                            ->default(0),
                        
                        Forms\Components\TextInput::make('total_with_vat')
                            ->label('الإجمالي شامل الضريبة')
                            ->numeric()
                            ->prefix('د.أ'),
                    ])->columns(3),

                Forms\Components\Section::make('تفاصيل إضافية')
                    ->schema([
                        Forms\Components\Select::make('currency')
                            ->label('العملة')
                            ->options([
                                'JOD' => 'دينار أردني',
                                'USD' => 'دولار أمريكي',
                                'EUR' => 'يورو',
                            ])
                            ->default('JOD'),
                        
                        Forms\Components\TextInput::make('validity_days')
                            ->label('مدة صلاحية العرض (أيام)')
                            ->numeric()
                            ->default(90),
                        
                        Forms\Components\TextInput::make('payment_terms')
                            ->label('شروط الدفع')
                            ->maxLength(500),
                        
                        Forms\Components\TextInput::make('delivery_period')
                            ->label('مدة التسليم')
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('price_breakdown')
                            ->label('تفصيل الأسعار')
                            ->rows(4)
                            ->columnSpanFull(),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tender.title')
                    ->label('العطاء')
                    ->searchable()
                    ->limit(25),
                
                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('المورد')
                    ->searchable()
                    ->limit(20),
                
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('المبلغ الإجمالي')
                    ->money('JOD')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('discount_percentage')
                    ->label('الخصم')
                    ->suffix('%')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('net_amount')
                    ->label('الصافي')
                    ->money('JOD')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('total_with_vat')
                    ->label('شامل الضريبة')
                    ->money('JOD')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('currency')
                    ->label('العملة')
                    ->badge(),
                
                Tables\Columns\TextColumn::make('validity_days')
                    ->label('الصلاحية')
                    ->suffix(' يوم'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('currency')
                    ->label('العملة')
                    ->options([
                        'JOD' => 'دينار أردني',
                        'USD' => 'دولار أمريكي',
                        'EUR' => 'يورو',
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenderFinancialProposals::route('/'),
            'create' => Pages\CreateTenderFinancialProposal::route('/create'),
            'edit' => Pages\EditTenderFinancialProposal::route('/{record}/edit'),
        ];
    }
}
