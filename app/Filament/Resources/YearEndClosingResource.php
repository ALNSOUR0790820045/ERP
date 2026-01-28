<?php

namespace App\Filament\Resources;

use App\Filament\Resources\YearEndClosingResource\Pages;
use App\Models\YearEndClosing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class YearEndClosingResource extends Resource
{
    protected static ?string $model = YearEndClosing::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    
    protected static ?string $navigationGroup = 'المالية';
    
    protected static ?string $modelLabel = 'إقفال سنة مالية';
    
    protected static ?string $pluralModelLabel = 'إقفال السنوات المالية';
    
    protected static ?int $navigationSort = 16;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الإقفال')
                    ->schema([
                        Forms\Components\Select::make('fiscal_year_id')
                            ->label('السنة المالية')
                            ->relationship('fiscalYear', 'name')
                            ->required(),
                            
                        Forms\Components\DatePicker::make('closing_date')
                            ->label('تاريخ الإقفال')
                            ->required()
                            ->default(now()),
                            
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'pending' => 'قيد الانتظار',
                                'in_progress' => 'قيد التنفيذ',
                                'completed' => 'مكتمل',
                                'cancelled' => 'ملغي',
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columns(3),
                    
                Forms\Components\Section::make('الأرصدة')
                    ->schema([
                        Forms\Components\TextInput::make('total_revenue')
                            ->label('إجمالي الإيرادات')
                            ->numeric()
                            ->prefix('د.أ'),
                            
                        Forms\Components\TextInput::make('total_expenses')
                            ->label('إجمالي المصروفات')
                            ->numeric()
                            ->prefix('د.أ'),
                            
                        Forms\Components\TextInput::make('net_profit')
                            ->label('صافي الربح')
                            ->numeric()
                            ->prefix('د.أ'),
                            
                        Forms\Components\TextInput::make('retained_earnings')
                            ->label('الأرباح المحتجزة')
                            ->numeric()
                            ->prefix('د.أ'),
                    ])->columns(4),
                    
                Forms\Components\Section::make('الموافقات')
                    ->schema([
                        Forms\Components\Select::make('prepared_by')
                            ->label('أعدّه')
                            ->relationship('preparedBy', 'name'),
                            
                        Forms\Components\Select::make('approved_by')
                            ->label('اعتمده')
                            ->relationship('approvedBy', 'name'),
                            
                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('تاريخ الاعتماد'),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fiscalYear.name')
                    ->label('السنة المالية')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('closing_date')
                    ->label('تاريخ الإقفال')
                    ->date('Y-m-d')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('الإيرادات')
                    ->money('JOD')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('total_expenses')
                    ->label('المصروفات')
                    ->money('JOD')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('net_profit')
                    ->label('صافي الربح')
                    ->money('JOD')
                    ->sortable()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'قيد الانتظار',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'pending' => 'gray',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('معتمد من')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('execute_closing')
                    ->label('تنفيذ الإقفال')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending'),
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
            'index' => Pages\ListYearEndClosings::route('/'),
            'create' => Pages\CreateYearEndClosing::route('/create'),
            'edit' => Pages\EditYearEndClosing::route('/{record}/edit'),
        ];
    }
}
