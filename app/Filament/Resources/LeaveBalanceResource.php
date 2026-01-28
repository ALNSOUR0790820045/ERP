<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveBalanceResource\Pages;
use App\Models\LeaveBalance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeaveBalanceResource extends Resource
{
    protected static ?string $model = LeaveBalance::class;
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationGroup = 'الموارد البشرية';
    protected static ?string $modelLabel = 'رصيد إجازات';
    protected static ?string $pluralModelLabel = 'أرصدة الإجازات';
    protected static ?int $navigationSort = 26;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الرصيد')
                ->schema([
                    Forms\Components\Select::make('employee_id')
                        ->label('الموظف')
                        ->relationship('employee', 'full_name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('leave_type_id')
                        ->label('نوع الإجازة')
                        ->relationship('leaveType', 'name_ar')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\TextInput::make('year')
                        ->label('السنة')
                        ->required()
                        ->numeric()
                        ->default(now()->year),
                    Forms\Components\TextInput::make('opening_balance')
                        ->label('الرصيد الافتتاحي')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('entitlement')
                        ->label('الاستحقاق')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('carry_forward')
                        ->label('مرحل')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('adjustment')
                        ->label('تعديل')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('used')
                        ->label('مستخدم')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('pending')
                        ->label('معلق')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('closing_balance')
                        ->label('الرصيد الختامي')
                        ->numeric()
                        ->default(0)
                        ->disabled()
                        ->dehydrated(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('الموظف')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('leaveType.name_ar')
                    ->label('نوع الإجازة'),
                Tables\Columns\TextColumn::make('year')
                    ->label('السنة')
                    ->sortable(),
                Tables\Columns\TextColumn::make('entitlement')
                    ->label('الاستحقاق'),
                Tables\Columns\TextColumn::make('used')
                    ->label('مستخدم'),
                Tables\Columns\TextColumn::make('pending')
                    ->label('معلق'),
                Tables\Columns\TextColumn::make('closing_balance')
                    ->label('الرصيد')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->label('السنة')
                    ->options(fn () => collect(range(now()->year - 2, now()->year + 1))
                        ->mapWithKeys(fn ($year) => [$year => $year])
                        ->toArray()),
                Tables\Filters\SelectFilter::make('leave_type_id')
                    ->label('نوع الإجازة')
                    ->relationship('leaveType', 'name_ar'),
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
            'index' => Pages\ListLeaveBalances::route('/'),
            'create' => Pages\CreateLeaveBalance::route('/create'),
            'edit' => Pages\EditLeaveBalance::route('/{record}/edit'),
        ];
    }
}
