<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChartOfAccountResource\Pages;
use App\Models\ChartOfAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ChartOfAccountResource extends Resource
{
    protected static ?string $model = ChartOfAccount::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'المالية والمحاسبة';
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'حساب';
    }

    public static function getPluralModelLabel(): string
    {
        return 'دليل الحسابات';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الحساب')->schema([
                Forms\Components\Select::make('company_id')
                    ->label('الشركة')
                    ->relationship('company', 'name_ar')
                    ->required(),
                Forms\Components\Select::make('parent_id')
                    ->label('الحساب الأب')
                    ->relationship('parent', 'name_ar'),
                Forms\Components\TextInput::make('code')
                    ->label('رقم الحساب')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('name_ar')
                    ->label('اسم الحساب (عربي)')
                    ->required(),
                Forms\Components\TextInput::make('name_en')
                    ->label('اسم الحساب (إنجليزي)'),
                Forms\Components\Select::make('account_type')
                    ->label('نوع الحساب')
                    ->required()
                    ->options([
                        'asset' => 'أصول',
                        'liability' => 'خصوم',
                        'equity' => 'حقوق ملكية',
                        'revenue' => 'إيرادات',
                        'expense' => 'مصروفات',
                    ]),
                Forms\Components\Select::make('account_nature')
                    ->label('طبيعة الحساب')
                    ->options([
                        'debit' => 'مدين',
                        'credit' => 'دائن',
                    ])
                    ->default('debit'),
                Forms\Components\TextInput::make('level')
                    ->label('المستوى')
                    ->numeric()
                    ->default(1),
                Forms\Components\Toggle::make('is_header')
                    ->label('حساب رئيسي')
                    ->default(false),
                Forms\Components\Toggle::make('is_bank_account')
                    ->label('حساب بنكي'),
                Forms\Components\Toggle::make('is_cash_account')
                    ->label('حساب نقدي'),
                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('رقم الحساب')->searchable(),
                Tables\Columns\TextColumn::make('name_ar')->label('اسم الحساب')->searchable(),
                Tables\Columns\TextColumn::make('account_type')
                    ->label('النوع')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'asset' => 'info',
                        'liability' => 'warning',
                        'equity' => 'success',
                        'revenue' => 'primary',
                        'expense' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('parent.name_ar')->label('الحساب الأب'),
                Tables\Columns\IconColumn::make('is_header')->label('رئيسي')->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('account_type')
                    ->label('النوع')
                    ->options([
                        'asset' => 'أصول',
                        'liability' => 'خصوم',
                        'equity' => 'حقوق ملكية',
                        'revenue' => 'إيرادات',
                        'expense' => 'مصروفات',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChartOfAccounts::route('/'),
            'create' => Pages\CreateChartOfAccount::route('/create'),
            'edit' => Pages\EditChartOfAccount::route('/{record}/edit'),
        ];
    }
}
