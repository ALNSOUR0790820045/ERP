<?php

namespace App\Filament\Resources\TenderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CompetitorsRelationManager extends RelationManager
{
    protected static string $relationship = 'competitors';

    protected static ?string $title = 'المنافسون';
    
    protected static ?string $modelLabel = 'منافس';
    
    protected static ?string $pluralModelLabel = 'المنافسون';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('company_name')
                    ->label('اسم الشركة')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('submitted_price')
                    ->label('السعر المقدم')
                    ->numeric()
                    ->prefix('د.أ'),
                Forms\Components\TextInput::make('rank')
                    ->label('الترتيب')
                    ->numeric(),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'qualified' => 'مؤهل',
                        'disqualified' => 'غير مؤهل',
                        'winner' => 'فائز',
                    ])
                    ->default('qualified'),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('company_name')
            ->defaultSort('rank')
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('الترتيب')
                    ->sortable(),
                Tables\Columns\TextColumn::make('company_name')
                    ->label('اسم الشركة')
                    ->searchable(),
                Tables\Columns\TextColumn::make('submitted_price')
                    ->label('السعر المقدم')
                    ->money('JOD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'qualified' => 'مؤهل',
                        'disqualified' => 'غير مؤهل',
                        'winner' => 'فائز',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'qualified' => 'success',
                        'disqualified' => 'danger',
                        'winner' => 'warning',
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
