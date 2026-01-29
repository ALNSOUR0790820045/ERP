<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BudgetResource\Pages;
use App\Models\Budget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BudgetResource extends Resource
{
    protected static ?string $model = Budget::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'المالية والمحاسبة';

    protected static ?string $modelLabel = 'موازنة';

    protected static ?string $pluralModelLabel = 'الموازنات';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الموازنة')
                    ->schema([
                        Forms\Components\TextInput::make('budget_number')
                            ->label('رقم الموازنة')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم')
                            ->required(),
                        Forms\Components\Select::make('fiscal_year_id')
                            ->label('السنة المالية')
                            ->relationship('fiscalYear', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('project_id')
                            ->label('المشروع')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('cost_center_id')
                            ->label('مركز التكلفة')
                            ->relationship('costCenter', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('المبالغ')
                    ->schema([
                        Forms\Components\TextInput::make('total_amount')
                            ->label('المبلغ الإجمالي')
                            ->numeric()
                            ->required()
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('spent_amount')
                            ->label('المبلغ المصروف')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('committed_amount')
                            ->label('المبلغ الملتزم به')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD'),
                    ])->columns(3),

                Forms\Components\Section::make('الحالة')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'draft' => 'مسودة',
                                'pending' => 'قيد الاعتماد',
                                'approved' => 'معتمد',
                                'active' => 'نشط',
                                'closed' => 'مغلق',
                            ])
                            ->default('draft'),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('budget_number')
                    ->label('الرقم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fiscalYear.name')
                    ->label('السنة المالية'),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('المشروع'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->money('JOD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('spent_amount')
                    ->label('المصروف')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('remaining')
                    ->label('المتبقي')
                    ->getStateUsing(fn ($record) => $record->total_amount - $record->spent_amount - $record->committed_amount)
                    ->money('JOD'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'pending',
                        'info' => 'approved',
                        'success' => 'active',
                        'danger' => 'closed',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'pending' => 'قيد الاعتماد',
                        'approved' => 'معتمد',
                        'active' => 'نشط',
                        'closed' => 'مغلق',
                    ]),
                Tables\Filters\SelectFilter::make('fiscal_year_id')
                    ->label('السنة المالية')
                    ->relationship('fiscalYear', 'name'),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBudgets::route('/'),
            'create' => Pages\CreateBudget::route('/create'),
            'edit' => Pages\EditBudget::route('/{record}/edit'),
        ];
    }
}
