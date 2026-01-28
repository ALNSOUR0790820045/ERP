<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EndOfServiceResource\Pages;
use App\Models\EndOfService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EndOfServiceResource extends Resource
{
    protected static ?string $model = EndOfService::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    
    protected static ?string $navigationGroup = 'الموارد البشرية';
    
    protected static ?string $modelLabel = 'مكافأة نهاية خدمة';
    
    protected static ?string $pluralModelLabel = 'مكافآت نهاية الخدمة';
    
    protected static ?int $navigationSort = 26;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الموظف')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('الموظف')
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->required(),
                            
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ التعيين')
                            ->required(),
                            
                        Forms\Components\DatePicker::make('end_date')
                            ->label('تاريخ انتهاء الخدمة')
                            ->required(),
                            
                        Forms\Components\TextInput::make('years_of_service')
                            ->label('سنوات الخدمة')
                            ->numeric()
                            ->step(0.01)
                            ->required(),
                    ])->columns(4),
                    
                Forms\Components\Section::make('تفاصيل الراتب')
                    ->schema([
                        Forms\Components\TextInput::make('basic_salary')
                            ->label('الراتب الأساسي')
                            ->numeric()
                            ->prefix('د.أ')
                            ->required(),
                            
                        Forms\Components\TextInput::make('last_salary')
                            ->label('آخر راتب')
                            ->numeric()
                            ->prefix('د.أ')
                            ->required(),
                            
                        Forms\Components\TextInput::make('average_salary')
                            ->label('متوسط الراتب')
                            ->numeric()
                            ->prefix('د.أ'),
                    ])->columns(3),
                    
                Forms\Components\Section::make('حساب المكافأة')
                    ->schema([
                        Forms\Components\Select::make('termination_reason')
                            ->label('سبب انتهاء الخدمة')
                            ->options([
                                'resignation' => 'استقالة',
                                'retirement' => 'تقاعد',
                                'contract_end' => 'انتهاء العقد',
                                'termination' => 'فصل',
                                'death' => 'وفاة',
                                'disability' => 'عجز',
                            ])
                            ->required(),
                            
                        Forms\Components\TextInput::make('entitlement_percentage')
                            ->label('نسبة الاستحقاق %')
                            ->numeric()
                            ->suffix('%')
                            ->default(100),
                            
                        Forms\Components\TextInput::make('calculated_amount')
                            ->label('المبلغ المحسوب')
                            ->numeric()
                            ->prefix('د.أ')
                            ->required(),
                            
                        Forms\Components\TextInput::make('final_amount')
                            ->label('المبلغ النهائي')
                            ->numeric()
                            ->prefix('د.أ')
                            ->required(),
                    ])->columns(4),
                    
                Forms\Components\Section::make('الخصومات والإضافات')
                    ->schema([
                        Forms\Components\TextInput::make('unpaid_leave_deduction')
                            ->label('خصم إجازات بدون راتب')
                            ->numeric()
                            ->prefix('د.أ')
                            ->default(0),
                            
                        Forms\Components\TextInput::make('loan_deduction')
                            ->label('خصم سلف')
                            ->numeric()
                            ->prefix('د.أ')
                            ->default(0),
                            
                        Forms\Components\TextInput::make('other_deductions')
                            ->label('خصومات أخرى')
                            ->numeric()
                            ->prefix('د.أ')
                            ->default(0),
                            
                        Forms\Components\TextInput::make('leave_balance_payment')
                            ->label('رصيد الإجازات')
                            ->numeric()
                            ->prefix('د.أ')
                            ->default(0),
                    ])->columns(4),
                    
                Forms\Components\Section::make('الدفع')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'calculated' => 'محسوب',
                                'approved' => 'معتمد',
                                'paid' => 'مدفوع',
                                'cancelled' => 'ملغي',
                            ])
                            ->default('calculated')
                            ->required(),
                            
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('تاريخ الدفع'),
                            
                        Forms\Components\TextInput::make('payment_reference')
                            ->label('رقم مرجع الدفع'),
                            
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
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('الموظف')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('end_date')
                    ->label('تاريخ الانتهاء')
                    ->date('Y-m-d')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('years_of_service')
                    ->label('سنوات الخدمة')
                    ->numeric(2)
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('termination_reason')
                    ->label('السبب')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'resignation' => 'استقالة',
                        'retirement' => 'تقاعد',
                        'contract_end' => 'انتهاء العقد',
                        'termination' => 'فصل',
                        'death' => 'وفاة',
                        'disability' => 'عجز',
                        default => $state,
                    })
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('final_amount')
                    ->label('المبلغ النهائي')
                    ->money('JOD')
                    ->sortable()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'calculated' => 'محسوب',
                        'approved' => 'معتمد',
                        'paid' => 'مدفوع',
                        'cancelled' => 'ملغي',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'calculated' => 'gray',
                        'approved' => 'info',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'calculated' => 'محسوب',
                        'approved' => 'معتمد',
                        'paid' => 'مدفوع',
                        'cancelled' => 'ملغي',
                    ]),
                Tables\Filters\SelectFilter::make('termination_reason')
                    ->label('سبب الانتهاء')
                    ->options([
                        'resignation' => 'استقالة',
                        'retirement' => 'تقاعد',
                        'contract_end' => 'انتهاء العقد',
                        'termination' => 'فصل',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('اعتماد')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'calculated'),
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
            'index' => Pages\ListEndOfServices::route('/'),
            'create' => Pages\CreateEndOfService::route('/create'),
            'edit' => Pages\EditEndOfService::route('/{record}/edit'),
        ];
    }
}
