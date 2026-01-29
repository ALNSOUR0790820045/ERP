<?php

namespace App\Filament\Resources\TenderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ClarificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'clarifications';

    protected static ?string $title = 'الاستفسارات والتوضيحات';
    
    protected static ?string $modelLabel = 'استفسار';
    
    protected static ?string $pluralModelLabel = 'الاستفسارات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('question_number')
                    ->label('رقم الاستفسار')
                    ->maxLength(50),
                Forms\Components\DatePicker::make('question_date')
                    ->label('تاريخ الاستفسار')
                    ->default(now()),
                Forms\Components\Textarea::make('question')
                    ->label('السؤال')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('answer')
                    ->label('الجواب')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('answer_date')
                    ->label('تاريخ الجواب'),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'answered' => 'تمت الإجابة',
                        'no_response' => 'بدون رد',
                    ])
                    ->default('pending'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question_number')
            ->columns([
                Tables\Columns\TextColumn::make('question_number')
                    ->label('رقم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('question_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('question')
                    ->label('السؤال')
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'قيد الانتظار',
                        'answered' => 'تمت الإجابة',
                        'no_response' => 'بدون رد',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'answered' => 'success',
                        'no_response' => 'danger',
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
