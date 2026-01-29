<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderProposalClosureResource\Pages;
use App\Models\Tenders\TenderProposalClosure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderProposalClosureResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = TenderProposalClosure::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    protected static ?string $navigationLabel = 'إغلاق العروض';
    protected static ?string $modelLabel = 'إغلاق عرض';
    protected static ?string $pluralModelLabel = 'إغلاق العروض';
    protected static ?int $navigationSort = 16;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('معلومات العرض')->schema([
                Forms\Components\Select::make('tender_id')
                    ->label('العطاء')
                    ->relationship('tender', 'name_ar')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\DateTimePicker::make('closing_date')
                    ->label('تاريخ ووقت الإغلاق')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('envelope_count')
                    ->label('عدد المظاريف')
                    ->numeric()
                    ->default(1)
                    ->minValue(1),
            ])->columns(3),

            Forms\Components\Section::make('حالة التسليم')->schema([
                Forms\Components\Toggle::make('is_delivered')
                    ->label('تم التسليم')
                    ->reactive(),
                Forms\Components\DateTimePicker::make('delivery_datetime')
                    ->label('تاريخ ووقت التسليم')
                    ->visible(fn($get) => $get('is_delivered')),
                Forms\Components\TextInput::make('delivery_person')
                    ->label('المسلم')
                    ->maxLength(255)
                    ->visible(fn($get) => $get('is_delivered')),
                Forms\Components\TextInput::make('receipt_number')
                    ->label('رقم الإيصال')
                    ->maxLength(100)
                    ->visible(fn($get) => $get('is_delivered')),
            ])->columns(2),

            Forms\Components\Section::make('ملاحظات')->schema([
                Forms\Components\Textarea::make('closing_notes')
                    ->label('ملاحظات الإغلاق')
                    ->rows(4)
                    ->columnSpanFull(),
            ]),
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
                Tables\Columns\TextColumn::make('closing_date')
                    ->label('تاريخ الإغلاق')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('envelope_count')
                    ->label('المظاريف')
                    ->alignCenter(),
                Tables\Columns\IconColumn::make('is_delivered')
                    ->label('مُسلم')
                    ->boolean(),
                Tables\Columns\TextColumn::make('delivery_datetime')
                    ->label('تاريخ التسليم')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('رقم الإيصال'),
            ])
            ->defaultSort('closing_date', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_delivered')
                    ->label('حالة التسليم'),
            ])
            ->actions([
                Tables\Actions\Action::make('markDelivered')
                    ->label('تسليم')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->visible(fn($record) => !$record->is_delivered)
                    ->form([
                        Forms\Components\TextInput::make('receipt_number')
                            ->label('رقم الإيصال')
                            ->required(),
                        Forms\Components\TextInput::make('delivery_person')
                            ->label('اسم المسلم'),
                    ])
                    ->action(function ($record, $data) {
                        $record->update([
                            'is_delivered' => true,
                            'delivery_datetime' => now(),
                            'receipt_number' => $data['receipt_number'],
                            'delivery_person' => $data['delivery_person'],
                            'delivered_by' => auth()->id(),
                        ]);
                    }),
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
            'index' => Pages\ListTenderProposalClosures::route('/'),
            'create' => Pages\CreateTenderProposalClosure::route('/create'),
            'edit' => Pages\EditTenderProposalClosure::route('/{record}/edit'),
        ];
    }
}
