<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderDiscoveryResource\Pages;
use App\Models\Tenders\TenderDiscovery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderDiscoveryResource extends Resource
{
    protected static ?string $model = TenderDiscovery::class;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    protected static ?string $navigationLabel = 'رصد العطاءات';
    protected static ?string $modelLabel = 'رصد عطاء';
    protected static ?string $pluralModelLabel = 'رصد العطاءات';
    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الرصد')->schema([
                Forms\Components\Select::make('tender_id')
                    ->label('العطاء')
                    ->relationship('tender', 'name_ar')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('source_id')
                    ->label('المصدر')
                    ->relationship('source', 'name_ar')
                    ->searchable()
                    ->preload(),
                Forms\Components\DatePicker::make('discovery_date')
                    ->label('تاريخ الرصد')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('source_reference')
                    ->label('رقم المرجع في المصدر')
                    ->maxLength(255),
            ])->columns(2),

            Forms\Components\Section::make('التفاصيل')->schema([
                Forms\Components\Select::make('priority')
                    ->label('الأولوية')
                    ->options(TenderDiscovery::PRIORITIES)
                    ->default('medium')
                    ->required(),
                Forms\Components\Toggle::make('is_verified')
                    ->label('تم التحقق'),
                Forms\Components\Textarea::make('initial_notes')
                    ->label('ملاحظات أولية')
                    ->rows(4)
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tender.name_ar')
                    ->label('العطاء')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\TextColumn::make('source.name_ar')
                    ->label('المصدر'),
                Tables\Columns\TextColumn::make('discovery_date')
                    ->label('تاريخ الرصد')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('الأولوية')
                    ->badge()
                    ->formatStateUsing(fn($state) => TenderDiscovery::PRIORITIES[$state] ?? $state)
                    ->color(fn($state) => match($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('متحقق')
                    ->boolean(),
                Tables\Columns\TextColumn::make('discoverer.name')
                    ->label('الراصد'),
            ])
            ->defaultSort('discovery_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('priority')
                    ->label('الأولوية')
                    ->options(TenderDiscovery::PRIORITIES),
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('متحقق'),
            ])
            ->actions([
                Tables\Actions\Action::make('verify')
                    ->label('تحقق')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn($record) => !$record->is_verified)
                    ->action(fn($record) => $record->verify(auth()->user())),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenderDiscoveries::route('/'),
            'create' => Pages\CreateTenderDiscovery::route('/create'),
            'edit' => Pages\EditTenderDiscovery::route('/{record}/edit'),
        ];
    }
}
