<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgressCertificateResource\Pages;
use App\Models\ProgressCertificate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProgressCertificateResource extends Resource
{
    protected static ?string $model = ProgressCertificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'العقود والمشاريع';
    protected static ?string $modelLabel = 'شهادة إنجاز';
    protected static ?string $pluralModelLabel = 'شهادات الإنجاز (IPC)';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المستخلص')
                    ->schema([
                        Forms\Components\Select::make('contract_id')
                            ->label('العقد')
                            ->relationship('contract', 'contract_number')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('project_id')
                            ->label('المشروع')
                            ->relationship('project', 'name')
                            ->searchable(),
                        Forms\Components\TextInput::make('certificate_number')
                            ->label('رقم الشهادة')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('ipc_number')
                            ->label('رقم المستخلص')
                            ->required()
                            ->numeric(),
                    ])->columns(2),

                Forms\Components\Section::make('الفترة')
                    ->schema([
                        Forms\Components\DatePicker::make('period_from')
                            ->label('من تاريخ')
                            ->required(),
                        Forms\Components\DatePicker::make('period_to')
                            ->label('إلى تاريخ')
                            ->required(),
                        Forms\Components\DatePicker::make('submission_date')
                            ->label('تاريخ التقديم')
                            ->default(now()),
                    ])->columns(3),

                Forms\Components\Section::make('المبالغ التراكمية')
                    ->schema([
                        Forms\Components\TextInput::make('cumulative_work_done')
                            ->label('الأعمال المنفذة (تراكمي)')
                            ->numeric()
                            ->required()
                            ->prefix('JOD')
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('previous_work_done')
                            ->label('الأعمال المنفذة (سابق)')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('current_work_done')
                            ->label('الأعمال المنفذة (حالي)')
                            ->numeric()
                            ->disabled()
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('materials_on_site')
                            ->label('مواد بالموقع')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('gross_amount')
                            ->label('إجمالي المبلغ')
                            ->numeric()
                            ->disabled()
                            ->prefix('JOD'),
                    ])->columns(3),

                Forms\Components\Section::make('الحسميات')
                    ->schema([
                        Forms\Components\TextInput::make('retention_rate')
                            ->label('نسبة المحتجز %')
                            ->numeric()
                            ->default(10)
                            ->suffix('%'),
                        Forms\Components\TextInput::make('retention_amount')
                            ->label('مبلغ المحتجز (تراكمي)')
                            ->numeric()
                            ->disabled()
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('current_retention')
                            ->label('المحتجز (حالي)')
                            ->numeric()
                            ->disabled()
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('advance_recovery')
                            ->label('استرداد الدفعة المقدمة (تراكمي)')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('current_advance_recovery')
                            ->label('استرداد الدفعة (حالي)')
                            ->numeric()
                            ->disabled()
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('other_deductions')
                            ->label('حسميات أخرى')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD'),
                    ])->columns(3),

                Forms\Components\Section::make('صافي المبلغ')
                    ->schema([
                        Forms\Components\TextInput::make('net_amount')
                            ->label('صافي المبلغ (تراكمي)')
                            ->numeric()
                            ->disabled()
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('previous_net')
                            ->label('صافي المبلغ (سابق)')
                            ->numeric()
                            ->default(0)
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('current_net')
                            ->label('صافي المبلغ (حالي)')
                            ->numeric()
                            ->disabled()
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('vat_rate')
                            ->label('نسبة الضريبة %')
                            ->numeric()
                            ->default(16)
                            ->suffix('%'),
                        Forms\Components\TextInput::make('vat_amount')
                            ->label('مبلغ الضريبة')
                            ->numeric()
                            ->disabled()
                            ->prefix('JOD'),
                        Forms\Components\TextInput::make('final_amount')
                            ->label('المبلغ النهائي')
                            ->numeric()
                            ->disabled()
                            ->prefix('JOD'),
                    ])->columns(3),

                Forms\Components\Section::make('الحالة والاعتماد')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(ProgressCertificate::STATUSES)
                            ->default('draft')
                            ->required(),
                        Forms\Components\Select::make('prepared_by')
                            ->label('أعده')
                            ->relationship('preparedBy', 'name')
                            ->default(auth()->id()),
                        Forms\Components\Select::make('checked_by')
                            ->label('دققه')
                            ->relationship('checkedBy', 'name'),
                        Forms\Components\Select::make('approved_by')
                            ->label('اعتمده')
                            ->relationship('approvedBy', 'name'),
                        Forms\Components\DatePicker::make('approval_date')
                            ->label('تاريخ الاعتماد'),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('certificate_number')
                    ->label('رقم الشهادة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ipc_number')
                    ->label('المستخلص')
                    ->sortable(),
                Tables\Columns\TextColumn::make('contract.contract_number')
                    ->label('العقد')
                    ->searchable(),
                Tables\Columns\TextColumn::make('period_to')
                    ->label('حتى تاريخ')
                    ->date(),
                Tables\Columns\TextColumn::make('cumulative_work_done')
                    ->label('الأعمال (تراكمي)')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('current_net')
                    ->label('المستحق (حالي)')
                    ->money('JOD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('final_amount')
                    ->label('المبلغ النهائي')
                    ->money('JOD')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'submitted',
                        'warning' => 'under_review',
                        'success' => 'approved',
                        'primary' => 'paid',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn ($state) => ProgressCertificate::STATUSES[$state] ?? $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('contract_id')
                    ->label('العقد')
                    ->relationship('contract', 'contract_number'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(ProgressCertificate::STATUSES),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('calculate')
                    ->label('حساب')
                    ->icon('heroicon-o-calculator')
                    ->action(function (ProgressCertificate $record) {
                        $record->calculate();
                        $record->save();
                    }),
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
            'index' => Pages\ListProgressCertificates::route('/'),
            'create' => Pages\CreateProgressCertificate::route('/create'),
            'edit' => Pages\EditProgressCertificate::route('/{record}/edit'),
        ];
    }
}
