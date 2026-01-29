<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'إعدادات النظام';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name_ar')
                    ->required(),
                Forms\Components\TextInput::make('name_en'),
                Forms\Components\TextInput::make('legal_name')
                    ->required(),
                Forms\Components\TextInput::make('registration_number')
                    ->required(),
                Forms\Components\TextInput::make('tax_number'),
                Forms\Components\TextInput::make('vat_number'),
                Forms\Components\TextInput::make('social_security_number'),
                Forms\Components\TextInput::make('classification_number'),
                Forms\Components\TextInput::make('classification_grade')
                    ->numeric(),
                Forms\Components\DatePicker::make('establishment_date'),
                Forms\Components\Textarea::make('address')
                    ->columnSpanFull(),
                Forms\Components\Select::make('city_id')
                    ->relationship('city', 'id'),
                Forms\Components\Select::make('country_id')
                    ->relationship('country', 'id'),
                Forms\Components\TextInput::make('postal_code'),
                Forms\Components\TextInput::make('phone')
                    ->tel(),
                Forms\Components\TextInput::make('fax'),
                Forms\Components\TextInput::make('email')
                    ->email(),
                Forms\Components\TextInput::make('website'),
                Forms\Components\TextInput::make('logo'),
                Forms\Components\Select::make('default_currency_id')
                    ->relationship('defaultCurrency', 'id'),
                Forms\Components\TextInput::make('fiscal_year_start')
                    ->required()
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('created_by')
                    ->numeric(),
                Forms\Components\TextInput::make('updated_by')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_en')
                    ->searchable(),
                Tables\Columns\TextColumn::make('legal_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('registration_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tax_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vat_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('social_security_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('classification_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('classification_grade')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('establishment_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('city.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('country.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('postal_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fax')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('website')
                    ->searchable(),
                Tables\Columns\TextColumn::make('logo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('defaultCurrency.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fiscal_year_start')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_by')
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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
