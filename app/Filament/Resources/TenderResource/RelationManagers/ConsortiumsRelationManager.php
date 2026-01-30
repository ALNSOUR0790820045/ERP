<?php

namespace App\Filament\Resources\TenderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Tenders\TenderConsortium;

class ConsortiumsRelationManager extends RelationManager
{
    protected static string $relationship = 'consortiums';

    protected static ?string $recordTitleAttribute = 'consortium_name';
    
    protected static ?string $title = 'الائتلافات';
    
    protected static ?string $modelLabel = 'ائتلاف';
    
    protected static ?string $pluralModelLabel = 'الائتلافات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الائتلاف')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('consortium_name')
                            ->label('اسم الائتلاف')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('agreement_type')
                            ->label('نوع الاتفاقية')
                            ->options([
                                'full_agreement' => 'اتفاقية ائتلاف كاملة',
                                'letter_of_intent' => 'خطاب نوايا',
                            ])
                            ->required(),
                        Forms\Components\DatePicker::make('agreement_date')
                            ->label('تاريخ الاتفاقية')
                            ->required(),
                        Forms\Components\DatePicker::make('agreement_expiry')
                            ->label('تاريخ انتهاء الاتفاقية'),
                    ]),
                    
                Forms\Components\Section::make('الشريك الرئيسي')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('lead_member_id')
                            ->label('الشريك الرئيسي')
                            ->relationship('leadMember', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('lead_member_share')
                            ->label('حصة الشريك الرئيسي (%)')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                    ]),
                    
                Forms\Components\Section::make('التفاصيل')
                    ->schema([
                        Forms\Components\Textarea::make('scope_of_work_distribution')
                            ->label('توزيع نطاق العمل')
                            ->rows(3)
                            ->helperText('وصف كيفية توزيع العمل بين أعضاء الائتلاف'),
                        Forms\Components\Textarea::make('financial_arrangements')
                            ->label('الترتيبات المالية')
                            ->rows(3),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(2),
                    ]),
                    
                Forms\Components\Section::make('التوثيق')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Forms\Components\Toggle::make('is_certified')
                            ->label('موثق رسمياً')
                            ->live(),
                        Forms\Components\TextInput::make('certification_number')
                            ->label('رقم التوثيق')
                            ->visible(fn (Forms\Get $get) => $get('is_certified')),
                        Forms\Components\DatePicker::make('certification_date')
                            ->label('تاريخ التوثيق')
                            ->visible(fn (Forms\Get $get) => $get('is_certified')),
                        Forms\Components\TextInput::make('certifying_authority')
                            ->label('جهة التوثيق')
                            ->visible(fn (Forms\Get $get) => $get('is_certified')),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('consortium_name')
            ->columns([
                Tables\Columns\TextColumn::make('consortium_name')
                    ->label('اسم الائتلاف')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('agreement_type')
                    ->label('نوع الاتفاقية')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'full_agreement' => 'اتفاقية كاملة',
                        'letter_of_intent' => 'خطاب نوايا',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'full_agreement' => 'success',
                        'letter_of_intent' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('leadMember.name')
                    ->label('الشريك الرئيسي')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lead_member_share')
                    ->label('حصة الشريك الرئيسي')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('members_count')
                    ->label('عدد الأعضاء')
                    ->counts('members'),
                Tables\Columns\IconColumn::make('is_certified')
                    ->label('موثق')
                    ->boolean(),
                Tables\Columns\TextColumn::make('agreement_date')
                    ->label('تاريخ الاتفاقية')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('agreement_type')
                    ->label('نوع الاتفاقية')
                    ->options([
                        'full_agreement' => 'اتفاقية كاملة',
                        'letter_of_intent' => 'خطاب نوايا',
                    ]),
                Tables\Filters\TernaryFilter::make('is_certified')
                    ->label('التوثيق'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
