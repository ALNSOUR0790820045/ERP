<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SequenceResource\Pages;
use App\Filament\Resources\SequenceResource\RelationManagers;
use App\Models\Sequence;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SequenceResource extends Resource
{
    protected static ?string $model = Sequence::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'إعدادات النظام';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'id'),
                Forms\Components\TextInput::make('code')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('document_type')
                    ->required(),
                Forms\Components\TextInput::make('prefix'),
                Forms\Components\TextInput::make('suffix'),
                Forms\Components\TextInput::make('next_number')
                    ->required()
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('min_digits')
                    ->required()
                    ->numeric()
                    ->default(4),
                Forms\Components\TextInput::make('reset_period')
                    ->required(),
                Forms\Components\Toggle::make('include_year')
                    ->required(),
                Forms\Components\Toggle::make('include_branch')
                    ->required(),
                Forms\Components\TextInput::make('current_year')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('document_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('prefix')
                    ->searchable(),
                Tables\Columns\TextColumn::make('suffix')
                    ->searchable(),
                Tables\Columns\TextColumn::make('next_number')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_digits')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reset_period')
                    ->searchable(),
                Tables\Columns\IconColumn::make('include_year')
                    ->boolean(),
                Tables\Columns\IconColumn::make('include_branch')
                    ->boolean(),
                Tables\Columns\TextColumn::make('current_year')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
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
            'index' => Pages\ListSequences::route('/'),
            'create' => Pages\CreateSequence::route('/create'),
            'edit' => Pages\EditSequence::route('/{record}/edit'),
        ];
    }
}
