<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncomeTaxBracketResource\Pages;
use App\Models\IncomeTaxBracket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IncomeTaxBracketResource extends Resource
{
    protected static ?string $model = IncomeTaxBracket::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationGroup = 'الموارد البشرية';
    protected static ?string $modelLabel = 'شريحة ضريبة الدخل';
    protected static ?string $pluralModelLabel = 'شرائح ضريبة الدخل';
    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('شرائح ضريبة الدخل الأردنية')
                    ->description('حسب قانون ضريبة الدخل الأردني')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الشريحة')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('year')
                            ->label('السنة')
                            ->numeric()
                            ->required()
                            ->default(date('Y')),
                        Forms\Components\Select::make('taxpayer_type')
                            ->label('نوع المكلف')
                            ->options(IncomeTaxBracket::TAXPAYER_TYPES)
                            ->required()
                            ->default('individual'),
                    ])->columns(3),

                Forms\Components\Section::make('حدود الشريحة')
                    ->schema([
                        Forms\Components\TextInput::make('from_amount')
                            ->label('من مبلغ')
                            ->numeric()
                            ->required()
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('to_amount')
                            ->label('إلى مبلغ')
                            ->numeric()
                            ->prefix('JOD')
                            ->helperText('اتركه فارغاً للشريحة الأخيرة'),
                        Forms\Components\TextInput::make('rate')
                            ->label('نسبة الضريبة %')
                            ->numeric()
                            ->required()
                            ->suffix('%'),
                        Forms\Components\TextInput::make('fixed_amount')
                            ->label('مبلغ ثابت')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD')
                            ->helperText('ضريبة ثابتة على الشرائح السابقة'),
                        Forms\Components\TextInput::make('exemption_amount')
                            ->label('مبلغ الإعفاء')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD'),
                    ])->columns(3),

                Forms\Components\Toggle::make('is_active')
                    ->label('فعّال')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('year')
                    ->label('السنة')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('الشريحة')
                    ->searchable(),
                Tables\Columns\TextColumn::make('taxpayer_type')
                    ->label('نوع المكلف')
                    ->formatStateUsing(fn ($state) => IncomeTaxBracket::TAXPAYER_TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('from_amount')
                    ->label('من')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('to_amount')
                    ->label('إلى')
                    ->money('JOD')
                    ->placeholder('غير محدود'),
                Tables\Columns\TextColumn::make('rate')
                    ->label('النسبة')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('exemption_amount')
                    ->label('الإعفاء')
                    ->money('JOD'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعّال')
                    ->boolean(),
            ])
            ->defaultSort('from_amount')
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->label('السنة')
                    ->options(fn () => IncomeTaxBracket::distinct()->pluck('year', 'year')->toArray()),
                Tables\Filters\SelectFilter::make('taxpayer_type')
                    ->label('نوع المكلف')
                    ->options(IncomeTaxBracket::TAXPAYER_TYPES),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ReplicateAction::make()
                    ->label('نسخ')
                    ->mutateRecordDataUsing(function (array $data): array {
                        $data['year'] = date('Y');
                        return $data;
                    }),
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
            'index' => Pages\ListIncomeTaxBrackets::route('/'),
            'create' => Pages\CreateIncomeTaxBracket::route('/create'),
            'edit' => Pages\EditIncomeTaxBracket::route('/{record}/edit'),
        ];
    }
}
