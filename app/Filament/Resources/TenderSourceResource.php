<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderSourceResource\Pages;
use App\Models\Tenders\TenderSource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderSourceResource extends Resource
{
    protected static ?string $model = TenderSource::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    protected static ?string $navigationLabel = 'مصادر الرصد';
    protected static ?string $modelLabel = 'مصدر رصد';
    protected static ?string $pluralModelLabel = 'مصادر الرصد';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('البيانات الأساسية')->schema([
                Forms\Components\TextInput::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->maxLength(255),
                Forms\Components\Select::make('source_type')
                    ->label('نوع المصدر')
                    ->options(TenderSource::SOURCE_TYPES)
                    ->required(),
                Forms\Components\TextInput::make('url')
                    ->label('الرابط')
                    ->url()
                    ->maxLength(255),
            ])->columns(2),

            Forms\Components\Section::make('بيانات الاتصال')->schema([
                Forms\Components\TextInput::make('contact_person')
                    ->label('جهة الاتصال')
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_phone')
                    ->label('الهاتف')
                    ->tel()
                    ->maxLength(50),
                Forms\Components\TextInput::make('contact_email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->maxLength(255),
            ])->columns(3),

            Forms\Components\Section::make('الاشتراك')->schema([
                Forms\Components\Toggle::make('requires_subscription')
                    ->label('يتطلب اشتراك'),
                Forms\Components\TextInput::make('subscription_cost')
                    ->label('تكلفة الاشتراك')
                    ->numeric()
                    ->prefix('JOD'),
                Forms\Components\TextInput::make('subscription_period')
                    ->label('فترة الاشتراك')
                    ->maxLength(100),
                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
            ])->columns(4),

            Forms\Components\Textarea::make('description')
                ->label('الوصف')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('source_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn($state) => TenderSource::SOURCE_TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('contact_person')
                    ->label('جهة الاتصال')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('contact_phone')
                    ->label('الهاتف')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('requires_subscription')
                    ->label('اشتراك')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
                Tables\Columns\TextColumn::make('discoveries_count')
                    ->label('العطاءات')
                    ->counts('discoveries'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source_type')
                    ->label('النوع')
                    ->options(TenderSource::SOURCE_TYPES),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة'),
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
            'index' => Pages\ListTenderSources::route('/'),
            'create' => Pages\CreateTenderSource::route('/create'),
            'edit' => Pages\EditTenderSource::route('/{record}/edit'),
        ];
    }
}
