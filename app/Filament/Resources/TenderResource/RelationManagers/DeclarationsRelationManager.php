<?php

namespace App\Filament\Resources\TenderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Tenders\TenderDeclaration;

class DeclarationsRelationManager extends RelationManager
{
    protected static string $relationship = 'declarations';

    protected static ?string $recordTitleAttribute = 'declaration_type';
    
    protected static ?string $title = 'الإقرارات والتعهدات';
    
    protected static ?string $modelLabel = 'إقرار';
    
    protected static ?string $pluralModelLabel = 'الإقرارات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الإقرار')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('declaration_type')
                            ->label('نوع الإقرار')
                            ->options(TenderDeclaration::getDeclarationTypes())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $defaultText = TenderDeclaration::getDefaultText($state);
                                if ($defaultText) {
                                    $set('declaration_text', $defaultText);
                                }
                            }),
                        Forms\Components\Toggle::make('is_required')
                            ->label('مطلوب')
                            ->default(true),
                        Forms\Components\Textarea::make('declaration_text')
                            ->label('نص الإقرار')
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('التوقيع')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Toggle::make('is_signed')
                            ->label('موقع')
                            ->live(),
                        Forms\Components\TextInput::make('signed_by')
                            ->label('الموقع')
                            ->visible(fn (Forms\Get $get) => $get('is_signed')),
                        Forms\Components\TextInput::make('signer_title')
                            ->label('صفة الموقع')
                            ->visible(fn (Forms\Get $get) => $get('is_signed')),
                        Forms\Components\DatePicker::make('signed_date')
                            ->label('تاريخ التوقيع')
                            ->visible(fn (Forms\Get $get) => $get('is_signed')),
                    ]),
                    
                Forms\Components\Section::make('التحقق')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Forms\Components\Toggle::make('is_verified')
                            ->label('تم التحقق')
                            ->live(),
                        Forms\Components\DatePicker::make('verified_date')
                            ->label('تاريخ التحقق')
                            ->visible(fn (Forms\Get $get) => $get('is_verified')),
                        Forms\Components\Textarea::make('verification_notes')
                            ->label('ملاحظات التحقق')
                            ->rows(2)
                            ->columnSpanFull()
                            ->visible(fn (Forms\Get $get) => $get('is_verified')),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('declaration_type')
            ->columns([
                Tables\Columns\TextColumn::make('declaration_type')
                    ->label('نوع الإقرار')
                    ->formatStateUsing(fn ($state) => TenderDeclaration::getDeclarationTypes()[$state] ?? $state)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_required')
                    ->label('مطلوب')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_signed')
                    ->label('موقع')
                    ->boolean(),
                Tables\Columns\TextColumn::make('signed_by')
                    ->label('الموقع')
                    ->searchable(),
                Tables\Columns\TextColumn::make('signed_date')
                    ->label('تاريخ التوقيع')
                    ->date(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('تم التحقق')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_signed')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('موقعة')
                    ->falseLabel('غير موقعة'),
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('التحقق')
                    ->placeholder('الكل')
                    ->trueLabel('تم التحقق')
                    ->falseLabel('لم يتم التحقق'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('createRequiredDeclarations')
                    ->label('إنشاء الإقرارات المطلوبة')
                    ->icon('heroicon-o-document-plus')
                    ->action(function () {
                        $tender = $this->getOwnerRecord();
                        TenderDeclaration::createRequiredDeclarations($tender);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('إنشاء الإقرارات المطلوبة')
                    ->modalDescription('سيتم إنشاء جميع الإقرارات المطلوبة حسب وثائق العطاءات الأردنية المعيارية'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
