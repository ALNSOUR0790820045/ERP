<?php

namespace App\Filament\Resources\ContractResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ClaimsRelationManager extends RelationManager
{
    protected static string $relationship = 'claims';
    
    protected static ?string $title = 'المطالبات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('claim_number')
                    ->label('رقم المطالبة')
                    ->required()
                    ->maxLength(50),
                    
                Forms\Components\Select::make('claim_type')
                    ->label('نوع المطالبة')
                    ->options([
                        'extension' => 'تمديد زمني',
                        'compensation' => 'تعويض مالي',
                        'disputed' => 'نزاع',
                        'cost_reimbursement' => 'استرداد تكاليف',
                    ])
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
                    
                Forms\Components\TextInput::make('clause_reference')
                    ->label('البند المرجعي'),
                    
                Forms\Components\DatePicker::make('event_date')
                    ->label('تاريخ الحدث')
                    ->required(),
                    
                Forms\Components\DatePicker::make('notice_date')
                    ->label('تاريخ الإشعار'),
                    
                Forms\Components\DatePicker::make('submission_date')
                    ->label('تاريخ التقديم')
                    ->required(),
                    
                Forms\Components\Toggle::make('notice_compliant')
                    ->label('مطابق للإشعار')
                    ->default(true),
                    
                Forms\Components\Section::make('المبالغ المطلوبة')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('time_claimed_days')
                            ->label('الأيام المطلوبة')
                            ->numeric()
                            ->default(0),
                            
                        Forms\Components\TextInput::make('cost_claimed')
                            ->label('التكلفة المطلوبة')
                            ->numeric()
                            ->default(0),
                            
                        Forms\Components\TextInput::make('loss_profit_claimed')
                            ->label('خسارة الأرباح')
                            ->numeric()
                            ->default(0),
                            
                        Forms\Components\TextInput::make('total_claimed')
                            ->label('الإجمالي المطلوب')
                            ->numeric()
                            ->default(0),
                    ]),
                    
                Forms\Components\Section::make('المعتمد')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('time_approved_days')
                            ->label('الأيام المعتمدة')
                            ->numeric(),
                            
                        Forms\Components\TextInput::make('cost_approved')
                            ->label('التكلفة المعتمدة')
                            ->numeric(),
                            
                        Forms\Components\TextInput::make('total_approved')
                            ->label('الإجمالي المعتمد')
                            ->numeric(),
                    ]),
                    
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'submitted' => 'مقدمة',
                        'under_review' => 'قيد المراجعة',
                        'partial' => 'موافقة جزئية',
                        'approved' => 'معتمدة',
                        'rejected' => 'مرفوضة',
                        'settled' => 'تم التسوية',
                    ])
                    ->default('submitted'),
                    
                Forms\Components\Textarea::make('review_notes')
                    ->label('ملاحظات المراجعة')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('claim_number')
                    ->label('رقم المطالبة')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->limit(40),
                    
                Tables\Columns\TextColumn::make('claim_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'extension' => 'تمديد زمني',
                        'compensation' => 'تعويض مالي',
                        'disputed' => 'نزاع',
                        'cost_reimbursement' => 'استرداد تكاليف',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('submission_date')
                    ->label('تاريخ التقديم')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('total_claimed')
                    ->label('المطلوب')
                    ->money('JOD'),
                    
                Tables\Columns\TextColumn::make('total_approved')
                    ->label('المعتمد')
                    ->money('JOD'),
                    
                Tables\Columns\TextColumn::make('time_claimed_days')
                    ->label('الأيام المطلوبة')
                    ->suffix(' يوم'),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'submitted' => 'مقدمة',
                        'under_review' => 'قيد المراجعة',
                        'partial' => 'جزئية',
                        'approved' => 'معتمدة',
                        'rejected' => 'مرفوضة',
                        'settled' => 'تم التسوية',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'partial' => 'warning',
                        'under_review' => 'info',
                        'settled' => 'primary',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('claim_type')
                    ->label('النوع')
                    ->options([
                        'extension' => 'تمديد زمني',
                        'compensation' => 'تعويض مالي',
                        'disputed' => 'نزاع',
                        'cost_reimbursement' => 'استرداد تكاليف',
                    ]),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'submitted' => 'مقدمة',
                        'under_review' => 'قيد المراجعة',
                        'partial' => 'جزئية',
                        'approved' => 'معتمدة',
                        'rejected' => 'مرفوضة',
                        'settled' => 'تم التسوية',
                    ]),
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
