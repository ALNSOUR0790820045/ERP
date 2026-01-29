<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderToProjectConversionResource\Pages;
use App\Models\Tenders\TenderToProjectConversion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderToProjectConversionResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = TenderToProjectConversion::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-circle';
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    protected static ?string $navigationLabel = 'تحويل لمشروع';
    protected static ?string $modelLabel = 'تحويل لمشروع';
    protected static ?string $pluralModelLabel = 'تحويلات المشاريع';
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('العطاء')->schema([
                Forms\Components\Select::make('tender_id')
                    ->label('العطاء')
                    ->relationship('tender', 'name_ar')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\DatePicker::make('conversion_date')
                    ->label('تاريخ التحويل')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options(TenderToProjectConversion::STATUSES)
                    ->default('pending')
                    ->required(),
            ])->columns(3),

            Forms\Components\Section::make('بيانات المشروع')->schema([
                Forms\Components\TextInput::make('project_code')
                    ->label('كود المشروع')
                    ->maxLength(50),
                Forms\Components\TextInput::make('project_name_ar')
                    ->label('اسم المشروع بالعربية')
                    ->maxLength(255),
                Forms\Components\TextInput::make('project_name_en')
                    ->label('اسم المشروع بالإنجليزية')
                    ->maxLength(255),
            ])->columns(3),

            Forms\Components\Section::make('بيانات العقد')->schema([
                Forms\Components\TextInput::make('contract_number')
                    ->label('رقم العقد')
                    ->maxLength(100),
                Forms\Components\DatePicker::make('contract_date')
                    ->label('تاريخ العقد'),
                Forms\Components\TextInput::make('contract_value')
                    ->label('قيمة العقد')
                    ->numeric()
                    ->prefix('JOD'),
                Forms\Components\TextInput::make('contract_duration_days')
                    ->label('مدة العقد (يوم)')
                    ->numeric(),
                Forms\Components\DatePicker::make('expected_start_date')
                    ->label('تاريخ البدء المتوقع'),
                Forms\Components\DatePicker::make('expected_end_date')
                    ->label('تاريخ الانتهاء المتوقع'),
            ])->columns(3),

            Forms\Components\Section::make('كفالة حسن التنفيذ')->schema([
                Forms\Components\Select::make('performance_bond_id')
                    ->label('كفالة التنفيذ')
                    ->relationship('performanceBond', 'bond_number')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('performance_bond_amount')
                    ->label('مبلغ الكفالة')
                    ->numeric()
                    ->prefix('JOD'),
                Forms\Components\DatePicker::make('performance_bond_date')
                    ->label('تاريخ الكفالة'),
            ])->columns(3),

            Forms\Components\Section::make('الدفعة المقدمة')->schema([
                Forms\Components\TextInput::make('advance_payment_amount')
                    ->label('مبلغ الدفعة المقدمة')
                    ->numeric()
                    ->prefix('JOD'),
                Forms\Components\TextInput::make('advance_payment_percentage')
                    ->label('النسبة')
                    ->numeric()
                    ->suffix('%'),
                Forms\Components\Select::make('advance_payment_bond_id')
                    ->label('كفالة الدفعة المقدمة')
                    ->relationship('advancePaymentBond', 'bond_number')
                    ->searchable()
                    ->preload(),
            ])->columns(3),

            Forms\Components\Textarea::make('notes')
                ->label('ملاحظات')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tender.name_ar')
                    ->label('العطاء')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\TextColumn::make('project_name_ar')
                    ->label('المشروع')
                    ->limit(25),
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('رقم العقد'),
                Tables\Columns\TextColumn::make('contract_value')
                    ->label('قيمة العقد')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('conversion_date')
                    ->label('تاريخ التحويل')
                    ->date(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn($state) => TenderToProjectConversion::STATUSES[$state] ?? $state)
                    ->color(fn($state) => match($state) {
                        'completed' => 'success',
                        'project_setup' => 'info',
                        'contract_signing' => 'warning',
                        'pending' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(TenderToProjectConversion::STATUSES),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('complete')
                    ->label('إكمال')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status !== 'completed')
                    ->action(fn($record) => $record->complete()),
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
            'index' => Pages\ListTenderToProjectConversions::route('/'),
            'create' => Pages\CreateTenderToProjectConversion::route('/create'),
            'edit' => Pages\EditTenderToProjectConversion::route('/{record}/edit'),
        ];
    }
}
