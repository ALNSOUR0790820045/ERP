<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderClarificationResource\Pages;
use App\Models\TenderClarification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderClarificationResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = TenderClarification::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    
    protected static ?string $navigationLabel = 'استيضاحات العطاءات';
    
    protected static ?string $modelLabel = 'استيضاح';
    
    protected static ?string $pluralModelLabel = 'استيضاحات العطاءات';
    
    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('السؤال')
                    ->schema([
                        Forms\Components\Select::make('tender_id')
                            ->relationship('tender', 'name_ar')
                            ->label('العطاء')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('question_number')
                            ->label('رقم السؤال')
                            ->required()
                            ->maxLength(50),
                        Forms\Components\DatePicker::make('question_date')
                            ->label('تاريخ السؤال')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('question_source')
                            ->label('مصدر السؤال')
                            ->options(TenderClarification::SOURCES)
                            ->required(),
                        Forms\Components\Textarea::make('question_ar')
                            ->label('السؤال (عربي)')
                            ->required()
                            ->rows(3),
                        Forms\Components\Textarea::make('question_en')
                            ->label('السؤال (إنجليزي)')
                            ->rows(3),
                    ])->columns(2),
                    
                Forms\Components\Section::make('الجواب')
                    ->schema([
                        Forms\Components\Textarea::make('answer_ar')
                            ->label('الجواب (عربي)')
                            ->rows(3),
                        Forms\Components\Textarea::make('answer_en')
                            ->label('الجواب (إنجليزي)')
                            ->rows(3),
                        Forms\Components\DatePicker::make('answer_date')
                            ->label('تاريخ الجواب'),
                        Forms\Components\TextInput::make('answer_reference')
                            ->label('مرجع الجواب')
                            ->maxLength(100),
                        Forms\Components\FileUpload::make('answer_document_path')
                            ->label('وثيقة الجواب')
                            ->directory('tender-clarifications'),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(TenderClarification::STATUSES)
                            ->default('pending'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('التأثير')
                    ->schema([
                        Forms\Components\Toggle::make('affects_boq')
                            ->label('يؤثر على جدول الكميات'),
                        Forms\Components\Toggle::make('affects_price')
                            ->label('يؤثر على السعر'),
                        Forms\Components\Toggle::make('affects_schedule')
                            ->label('يؤثر على البرنامج الزمني'),
                        Forms\Components\Textarea::make('impact_notes')
                            ->label('ملاحظات التأثير')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tender.name_ar')
                    ->label('العطاء')
                    ->searchable()
                    ->limit(25),
                Tables\Columns\TextColumn::make('question_number')
                    ->label('رقم السؤال')
                    ->searchable(),
                Tables\Columns\TextColumn::make('question_date')
                    ->label('تاريخ السؤال')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('question_source')
                    ->label('المصدر')
                    ->badge()
                    ->formatStateUsing(fn ($state) => TenderClarification::SOURCES[$state] ?? $state),
                Tables\Columns\TextColumn::make('question_ar')
                    ->label('السؤال')
                    ->limit(40),
                Tables\Columns\IconColumn::make('affects_boq')
                    ->label('BOQ')
                    ->boolean(),
                Tables\Columns\IconColumn::make('affects_price')
                    ->label('سعر')
                    ->boolean(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => TenderClarification::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'answered' => 'success',
                        'pending' => 'warning',
                        'no_response' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(TenderClarification::STATUSES),
                Tables\Filters\SelectFilter::make('question_source')
                    ->label('المصدر')
                    ->options(TenderClarification::SOURCES),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListTenderClarifications::route('/'),
            'create' => Pages\CreateTenderClarification::route('/create'),
            'edit' => Pages\EditTenderClarification::route('/{record}/edit'),
        ];
    }
}
