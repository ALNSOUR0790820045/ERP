<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinancialStatementResource\Pages;
use App\Models\FinancialStatement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FinancialStatementResource extends Resource
{
    protected static ?string $model = FinancialStatement::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    
    protected static ?string $navigationGroup = 'المالية';
    
    protected static ?string $modelLabel = 'قائمة مالية';
    
    protected static ?string $pluralModelLabel = 'القوائم المالية';
    
    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات القائمة المالية')
                    ->schema([
                        Forms\Components\Select::make('statement_type')
                            ->label('نوع القائمة')
                            ->options([
                                'balance_sheet' => 'الميزانية العمومية',
                                'income_statement' => 'قائمة الدخل',
                                'cash_flow' => 'قائمة التدفقات النقدية',
                                'equity_statement' => 'قائمة حقوق الملكية',
                                'trial_balance' => 'ميزان المراجعة',
                            ])
                            ->required(),
                            
                        Forms\Components\Select::make('fiscal_year_id')
                            ->label('السنة المالية')
                            ->relationship('fiscalYear', 'name')
                            ->required(),
                            
                        Forms\Components\DatePicker::make('period_start')
                            ->label('بداية الفترة')
                            ->required(),
                            
                        Forms\Components\DatePicker::make('period_end')
                            ->label('نهاية الفترة')
                            ->required(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('البيانات المالية')
                    ->schema([
                        Forms\Components\KeyValue::make('data')
                            ->label('البيانات')
                            ->keyLabel('البند')
                            ->valueLabel('القيمة'),
                            
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'draft' => 'مسودة',
                                'final' => 'نهائي',
                                'approved' => 'معتمد',
                            ])
                            ->default('draft')
                            ->required(),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('statement_type')
                    ->label('نوع القائمة')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'balance_sheet' => 'الميزانية العمومية',
                        'income_statement' => 'قائمة الدخل',
                        'cash_flow' => 'التدفقات النقدية',
                        'equity_statement' => 'حقوق الملكية',
                        'trial_balance' => 'ميزان المراجعة',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'balance_sheet' => 'primary',
                        'income_statement' => 'success',
                        'cash_flow' => 'info',
                        'equity_statement' => 'warning',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('fiscalYear.name')
                    ->label('السنة المالية')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('period_start')
                    ->label('من')
                    ->date('Y-m-d')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('period_end')
                    ->label('إلى')
                    ->date('Y-m-d')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'مسودة',
                        'final' => 'نهائي',
                        'approved' => 'معتمد',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'draft' => 'gray',
                        'final' => 'info',
                        'approved' => 'success',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('statement_type')
                    ->label('نوع القائمة')
                    ->options([
                        'balance_sheet' => 'الميزانية العمومية',
                        'income_statement' => 'قائمة الدخل',
                        'cash_flow' => 'التدفقات النقدية',
                        'equity_statement' => 'حقوق الملكية',
                        'trial_balance' => 'ميزان المراجعة',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'final' => 'نهائي',
                        'approved' => 'معتمد',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generate_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success'),
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
            'index' => Pages\ListFinancialStatements::route('/'),
            'create' => Pages\CreateFinancialStatement::route('/create'),
            'edit' => Pages\EditFinancialStatement::route('/{record}/edit'),
        ];
    }
}
