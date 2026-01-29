<?php

namespace App\Filament\Resources\TenderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'وثائق العطاء';
    
    protected static ?string $modelLabel = 'وثيقة';
    
    protected static ?string $pluralModelLabel = 'الوثائق';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم الوثيقة')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('document_type')
                    ->label('نوع الوثيقة')
                    ->options([
                        'boq' => 'جدول الكميات',
                        'drawings' => 'المخططات',
                        'specifications' => 'المواصفات',
                        'contract' => 'وثائق العقد',
                        'addendum' => 'ملحق',
                        'clarification' => 'توضيح',
                        'other' => 'أخرى',
                    ])
                    ->required(),
                Forms\Components\FileUpload::make('file_path')
                    ->label('الملف')
                    ->directory('tender-documents')
                    ->preserveFilenames()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->label('الوصف')
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('version')
                    ->label('الإصدار')
                    ->default('1.0'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الوثيقة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('document_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'boq' => 'جدول الكميات',
                        'drawings' => 'المخططات',
                        'specifications' => 'المواصفات',
                        'contract' => 'وثائق العقد',
                        'addendum' => 'ملحق',
                        'clarification' => 'توضيح',
                        default => 'أخرى',
                    }),
                Tables\Columns\TextColumn::make('version')
                    ->label('الإصدار'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الرفع')
                    ->dateTime('Y-m-d')
                    ->sortable(),
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
