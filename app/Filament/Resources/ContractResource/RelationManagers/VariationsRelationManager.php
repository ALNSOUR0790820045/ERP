<?php

namespace App\Filament\Resources\ContractResource\RelationManagers;

use App\Enums\VariationStatus;
use App\Enums\VariationType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'variations';
    
    protected static ?string $title = 'الأوامر التغييرية';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('vo_number')
                    ->label('رقم الأمر التغييري')
                    ->required()
                    ->maxLength(50),
                    
                Forms\Components\Select::make('vo_type')
                    ->label('النوع')
                    ->options(VariationType::class)
                    ->required(),
                    
                Forms\Components\TextInput::make('title')
                    ->label('العنوان')
                    ->required()
                    ->columnSpanFull(),
                    
                Forms\Components\Textarea::make('description')
                    ->label('الوصف')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                    
                Forms\Components\Select::make('requested_by')
                    ->label('مقدم من')
                    ->options([
                        'employer' => 'صاحب العمل',
                        'engineer' => 'المهندس',
                        'contractor' => 'المقاول',
                    ])
                    ->required(),
                    
                Forms\Components\DatePicker::make('request_date')
                    ->label('تاريخ الطلب')
                    ->required(),
                    
                Forms\Components\TextInput::make('instruction_reference')
                    ->label('مرجع التعليمات'),
                    
                Forms\Components\TextInput::make('submitted_amount')
                    ->label('المبلغ المقدم')
                    ->numeric()
                    ->default(0),
                    
                Forms\Components\TextInput::make('approved_amount')
                    ->label('المبلغ المعتمد')
                    ->numeric()
                    ->default(0),
                    
                Forms\Components\TextInput::make('time_extension_days')
                    ->label('التمديد الزمني (أيام)')
                    ->numeric()
                    ->default(0),
                    
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options(VariationStatus::class)
                    ->default(VariationStatus::DRAFT),
                    
                Forms\Components\Textarea::make('reason')
                    ->label('السبب')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vo_number')
                    ->label('رقم VO')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->limit(40),
                    
                Tables\Columns\TextColumn::make('vo_type')
                    ->label('النوع')
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('request_date')
                    ->label('تاريخ الطلب')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('submitted_amount')
                    ->label('المبلغ المقدم')
                    ->money('JOD'),
                    
                Tables\Columns\TextColumn::make('approved_amount')
                    ->label('المبلغ المعتمد')
                    ->money('JOD'),
                    
                Tables\Columns\TextColumn::make('time_extension_days')
                    ->label('التمديد')
                    ->suffix(' يوم'),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(VariationStatus::class),
                    
                Tables\Filters\SelectFilter::make('vo_type')
                    ->label('النوع')
                    ->options(VariationType::class),
            ])
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
            ])
            ->defaultSort('created_at', 'desc');
    }
}
