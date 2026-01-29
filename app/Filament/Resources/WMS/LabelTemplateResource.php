<?php

namespace App\Filament\Resources\WMS;

use App\Filament\Resources\WMS\LabelTemplateResource\Pages;
use App\Filament\Resources\WMS\LabelTemplateResource\RelationManagers;
use App\Models\WMS\LabelTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LabelTemplateResource extends Resource
{
    protected static ?string $model = LabelTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required(),
                Forms\Components\TextInput::make('name_ar')
                    ->required(),
                Forms\Components\TextInput::make('name_en')
                    ->required(),
                Forms\Components\TextInput::make('label_type')
                    ->required(),
                Forms\Components\TextInput::make('width_mm')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('height_mm')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('orientation')
                    ->required(),
                Forms\Components\Textarea::make('layout')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('fields')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('zpl_template')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('html_template')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('include_barcode')
                    ->required(),
                Forms\Components\Toggle::make('include_qr')
                    ->required(),
                Forms\Components\TextInput::make('barcode_position')
                    ->required(),
                Forms\Components\Toggle::make('is_default')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_ar')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_en')
                    ->searchable(),
                Tables\Columns\TextColumn::make('label_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('width_mm')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('height_mm')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('orientation')
                    ->searchable(),
                Tables\Columns\IconColumn::make('include_barcode')
                    ->boolean(),
                Tables\Columns\IconColumn::make('include_qr')
                    ->boolean(),
                Tables\Columns\TextColumn::make('barcode_position')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
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
            'index' => Pages\ListLabelTemplates::route('/'),
            'create' => Pages\CreateLabelTemplate::route('/create'),
            'edit' => Pages\EditLabelTemplate::route('/{record}/edit'),
        ];
    }
}
