<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocialSecuritySettingResource\Pages;
use App\Models\SocialSecuritySetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SocialSecuritySettingResource extends Resource
{
    protected static ?string $model = SocialSecuritySetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'الموارد البشرية';
    protected static ?string $modelLabel = 'إعدادات الضمان الاجتماعي';
    protected static ?string $pluralModelLabel = 'إعدادات الضمان الاجتماعي';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('إعدادات الضمان الاجتماعي الأردني')
                    ->description('نسب الاشتراك حسب مؤسسة الضمان الاجتماعي الأردنية')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الإعداد')
                            ->required()
                            ->maxLength(255)
                            ->default('الضمان الاجتماعي الأردني'),
                        Forms\Components\TextInput::make('employer_rate')
                            ->label('نسبة صاحب العمل %')
                            ->numeric()
                            ->required()
                            ->default(14.25)
                            ->suffix('%')
                            ->helperText('النسبة الحالية 14.25%'),
                        Forms\Components\TextInput::make('employee_rate')
                            ->label('نسبة العامل %')
                            ->numeric()
                            ->required()
                            ->default(7.5)
                            ->suffix('%')
                            ->helperText('النسبة الحالية 7.5%'),
                        Forms\Components\TextInput::make('total_rate')
                            ->label('إجمالي النسبة %')
                            ->numeric()
                            ->disabled()
                            ->suffix('%'),
                    ])->columns(2),

                Forms\Components\Section::make('حدود الراتب')
                    ->schema([
                        Forms\Components\TextInput::make('minimum_wage')
                            ->label('الحد الأدنى للأجور')
                            ->numeric()
                            ->prefix('JOD')
                            ->default(260)
                            ->helperText('الحد الأدنى للأجور في الأردن'),
                        Forms\Components\TextInput::make('maximum_contributable_salary')
                            ->label('الحد الأعلى للراتب الخاضع')
                            ->numeric()
                            ->prefix('JOD')
                            ->helperText('اتركه فارغاً إذا لا يوجد حد أعلى'),
                    ])->columns(2),

                Forms\Components\Section::make('الفترة')
                    ->schema([
                        Forms\Components\DatePicker::make('effective_from')
                            ->label('سارية من')
                            ->required()
                            ->default(now()->startOfYear()),
                        Forms\Components\DatePicker::make('effective_to')
                            ->label('سارية حتى')
                            ->helperText('اتركه فارغاً للسريان المستمر'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('فعّال')
                            ->default(true),
                    ])->columns(3),

                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('employer_rate')
                    ->label('نسبة صاحب العمل')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('employee_rate')
                    ->label('نسبة العامل')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('total_rate')
                    ->label('الإجمالي')
                    ->suffix('%')
                    ->state(fn ($record) => $record->employer_rate + $record->employee_rate),
                Tables\Columns\TextColumn::make('minimum_wage')
                    ->label('الحد الأدنى')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('effective_from')
                    ->label('من')
                    ->date(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعّال')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة'),
            ])
            ->actions([
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSocialSecuritySettings::route('/'),
            'create' => Pages\CreateSocialSecuritySetting::route('/create'),
            'edit' => Pages\EditSocialSecuritySetting::route('/{record}/edit'),
        ];
    }
}
