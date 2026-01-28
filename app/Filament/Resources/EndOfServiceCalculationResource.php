<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EndOfServiceCalculationResource\Pages;
use App\Models\EndOfServiceCalculation;
use App\Models\Employee;
use App\Models\EndOfServiceSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;

class EndOfServiceCalculationResource extends Resource
{
    protected static ?string $model = EndOfServiceCalculation::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'الموارد البشرية';

    protected static ?string $navigationLabel = 'نهاية الخدمة';

    protected static ?string $modelLabel = 'حساب نهاية الخدمة';

    protected static ?string $pluralModelLabel = 'حسابات نهاية الخدمة';

    protected static ?int $navigationSort = 55;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الموظف')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('employee_id')
                                    ->label('الموظف')
                                    ->relationship('employee', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        if ($state) {
                                            $employee = Employee::find($state);
                                            if ($employee) {
                                                $set('hire_date', $employee->hire_date ?? $employee->joining_date);
                                                $set('basic_salary', $employee->basic_salary ?? $employee->salary ?? 0);
                                                $set('total_allowances', $employee->allowances ?? 0);
                                            }
                                        }
                                    }),

                                Forms\Components\TextInput::make('calculation_number')
                                    ->label('رقم الحساب')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\Select::make('termination_type')
                                    ->label('نوع إنهاء الخدمة')
                                    ->options(EndOfServiceCalculation::TERMINATION_TYPES)
                                    ->required()
                                    ->native(false),
                            ]),
                    ]),

                Forms\Components\Section::make('فترة الخدمة')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\DatePicker::make('hire_date')
                                    ->label('تاريخ التعيين')
                                    ->required(),

                                Forms\Components\DatePicker::make('termination_date')
                                    ->label('تاريخ إنهاء الخدمة')
                                    ->required()
                                    ->default(now()),

                                Forms\Components\Placeholder::make('service_years_display')
                                    ->label('سنوات الخدمة')
                                    ->content(function ($get) {
                                        $hire = $get('hire_date');
                                        $term = $get('termination_date');
                                        if ($hire && $term) {
                                            $diff = \Carbon\Carbon::parse($hire)->diff(\Carbon\Carbon::parse($term));
                                            return "{$diff->y} سنة و {$diff->m} شهر و {$diff->d} يوم";
                                        }
                                        return '-';
                                    }),

                                Forms\Components\Hidden::make('service_years'),
                                Forms\Components\Hidden::make('service_months'),
                                Forms\Components\Hidden::make('service_days'),
                                Forms\Components\Hidden::make('total_service_years'),
                            ]),
                    ]),

                Forms\Components\Section::make('بيانات الراتب')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('basic_salary')
                                    ->label('الراتب الأساسي')
                                    ->numeric()
                                    ->required()
                                    ->prefix('JOD'),

                                Forms\Components\TextInput::make('total_allowances')
                                    ->label('إجمالي البدلات')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('JOD'),

                                Forms\Components\Placeholder::make('calculation_salary_display')
                                    ->label('الراتب للحساب')
                                    ->content(function ($get) {
                                        $settings = EndOfServiceSetting::getEffectiveSettings();
                                        $basic = floatval($get('basic_salary') ?? 0);
                                        $allowances = floatval($get('total_allowances') ?? 0);
                                        
                                        if ($settings && $settings->calculation_basis === 'gross_salary') {
                                            return number_format($basic + $allowances, 3) . ' JOD';
                                        }
                                        return number_format($basic, 3) . ' JOD';
                                    }),
                            ]),
                    ]),

                Forms\Components\Section::make('الخصومات')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('loan_deductions')
                                    ->label('خصم القروض')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('JOD'),

                                Forms\Components\TextInput::make('advance_deductions')
                                    ->label('خصم السلف')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('JOD'),

                                Forms\Components\TextInput::make('other_deductions')
                                    ->label('خصومات أخرى')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('JOD'),
                            ]),

                        Forms\Components\Textarea::make('deduction_notes')
                            ->label('ملاحظات الخصومات')
                            ->rows(2),
                    ]),

                Forms\Components\Section::make('الملاحظات')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3),

                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'draft' => 'مسودة',
                                'pending_approval' => 'بانتظار الموافقة',
                                'approved' => 'معتمد',
                                'paid' => 'مدفوع',
                                'cancelled' => 'ملغى',
                            ])
                            ->default('draft'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('calculation_number')
                    ->label('رقم الحساب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('employee.name')
                    ->label('الموظف')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('termination_type')
                    ->label('نوع الإنهاء')
                    ->formatStateUsing(fn ($state) => EndOfServiceCalculation::TERMINATION_TYPES[$state] ?? $state)
                    ->badge(),

                Tables\Columns\TextColumn::make('total_service_years')
                    ->label('سنوات الخدمة')
                    ->numeric(2)
                    ->suffix(' سنة'),

                Tables\Columns\TextColumn::make('gross_entitlement')
                    ->label('الاستحقاق الإجمالي')
                    ->money('JOD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('net_entitlement')
                    ->label('صافي الاستحقاق')
                    ->money('JOD')
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn ($record) => $record->status_color)
                    ->formatStateUsing(fn ($record) => $record->status_label),

                Tables\Columns\TextColumn::make('termination_date')
                    ->label('تاريخ الإنهاء')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('termination_type')
                    ->label('نوع الإنهاء')
                    ->options(EndOfServiceCalculation::TERMINATION_TYPES),

                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'pending_approval' => 'بانتظار الموافقة',
                        'approved' => 'معتمد',
                        'paid' => 'مدفوع',
                        'cancelled' => 'ملغى',
                    ]),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('calculate')
                        ->label('حساب الاستحقاق')
                        ->icon('heroicon-o-calculator')
                        ->color('info')
                        ->visible(fn ($record) => $record->status === 'draft')
                        ->action(function ($record) {
                            $record->calculate()->save();
                            Notification::make()
                                ->title('تم حساب الاستحقاق')
                                ->body('صافي الاستحقاق: ' . number_format($record->net_entitlement, 3) . ' JOD')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('approve')
                        ->label('اعتماد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->status === 'pending_approval')
                        ->action(function ($record) {
                            $record->approve(auth()->id());
                            Notification::make()
                                ->title('تم اعتماد الحساب')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('mark_paid')
                        ->label('تسجيل الدفع')
                        ->icon('heroicon-o-banknotes')
                        ->color('warning')
                        ->visible(fn ($record) => $record->status === 'approved')
                        ->form([
                            Forms\Components\DatePicker::make('payment_date')
                                ->label('تاريخ الدفع')
                                ->required()
                                ->default(now()),
                            Forms\Components\TextInput::make('payment_reference')
                                ->label('مرجع الدفع'),
                        ])
                        ->action(function ($record, array $data) {
                            $record->markAsPaid($data['payment_date'], $data['payment_reference']);
                            Notification::make()
                                ->title('تم تسجيل الدفع')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الحساب')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('calculation_number')
                                    ->label('رقم الحساب'),
                                Infolists\Components\TextEntry::make('employee.name')
                                    ->label('الموظف'),
                                Infolists\Components\TextEntry::make('termination_type')
                                    ->label('نوع الإنهاء')
                                    ->formatStateUsing(fn ($state) => EndOfServiceCalculation::TERMINATION_TYPES[$state] ?? $state),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('الحالة')
                                    ->badge()
                                    ->color(fn ($record) => $record->status_color),
                            ]),
                    ]),

                Infolists\Components\Section::make('فترة الخدمة')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('hire_date')
                                    ->label('تاريخ التعيين')
                                    ->date(),
                                Infolists\Components\TextEntry::make('termination_date')
                                    ->label('تاريخ الإنهاء')
                                    ->date(),
                                Infolists\Components\TextEntry::make('service_period_text')
                                    ->label('مدة الخدمة'),
                                Infolists\Components\TextEntry::make('total_service_years')
                                    ->label('إجمالي السنوات')
                                    ->numeric(4),
                            ]),
                    ]),

                Infolists\Components\Section::make('تفاصيل الحساب')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('basic_salary')
                                    ->label('الراتب الأساسي')
                                    ->money('JOD'),
                                Infolists\Components\TextEntry::make('total_allowances')
                                    ->label('البدلات')
                                    ->money('JOD'),
                                Infolists\Components\TextEntry::make('calculation_salary')
                                    ->label('الراتب للحساب')
                                    ->money('JOD'),
                            ]),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('rate_applied')
                                    ->label('النسبة المطبقة')
                                    ->numeric(4),
                                Infolists\Components\TextEntry::make('gross_entitlement')
                                    ->label('الاستحقاق الإجمالي')
                                    ->money('JOD'),
                                Infolists\Components\TextEntry::make('total_deductions')
                                    ->label('إجمالي الخصومات')
                                    ->money('JOD'),
                            ]),

                        Infolists\Components\TextEntry::make('net_entitlement')
                            ->label('صافي الاستحقاق')
                            ->money('JOD')
                            ->weight(FontWeight::Bold)
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
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
            'index' => Pages\ListEndOfServiceCalculations::route('/'),
            'create' => Pages\CreateEndOfServiceCalculation::route('/create'),
            'view' => Pages\ViewEndOfServiceCalculation::route('/{record}'),
            'edit' => Pages\EditEndOfServiceCalculation::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
