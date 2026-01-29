<?php

namespace App\Filament\Resources\WMS;

use App\Filament\Resources\WMS\RfDeviceResource\Pages;
use App\Filament\Resources\WMS\RfDeviceResource\RelationManagers;
use App\Models\WMS\RfDevice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RfDeviceResource extends Resource
{
    protected static ?string $model = RfDevice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('device_code')
                    ->required(),
                Forms\Components\TextInput::make('device_name')
                    ->required(),
                Forms\Components\TextInput::make('device_type')
                    ->required(),
                Forms\Components\TextInput::make('manufacturer'),
                Forms\Components\TextInput::make('model'),
                Forms\Components\TextInput::make('serial_number'),
                Forms\Components\TextInput::make('mac_address'),
                Forms\Components\TextInput::make('ip_address'),
                Forms\Components\Select::make('warehouse_id')
                    ->relationship('warehouse', 'id'),
                Forms\Components\Select::make('assigned_user_id')
                    ->relationship('assignedUser', 'name'),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\TextInput::make('battery_level')
                    ->numeric(),
                Forms\Components\Textarea::make('capabilities')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('settings')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('last_activity_at'),
                Forms\Components\DateTimePicker::make('last_sync_at'),
                Forms\Components\DatePicker::make('purchase_date'),
                Forms\Components\DatePicker::make('warranty_expiry'),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('device_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('device_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('device_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('manufacturer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('model')
                    ->searchable(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mac_address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('warehouse.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('battery_level')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_activity_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_sync_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('warranty_expiry')
                    ->date()
                    ->sortable(),
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
            'index' => Pages\ListRfDevices::route('/'),
            'create' => Pages\CreateRfDevice::route('/create'),
            'edit' => Pages\EditRfDevice::route('/{record}/edit'),
        ];
    }
}
