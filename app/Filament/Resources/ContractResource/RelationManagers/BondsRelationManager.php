<?php

namespace App\Filament\Resources\ContractResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BondsRelationManager extends RelationManager
{
    protected static string $relationship = 'bonds';
    
    protected static ?string $title = 'الضمانات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('bond_type')
                    ->label('نوع الضمان')
                    ->options([
                        'performance' => 'كفالة حسن التنفيذ',
                        'advance' => 'كفالة الدفعة المقدمة',
                        'retention' => 'كفالة المحتجز',
                        'tender' => 'كفالة العطاء',
                    ])
                    ->required(),
                    
                Forms\Components\TextInput::make('bond_number')
                    ->label('رقم الضمان')
                    ->maxLength(100),
                    
                Forms\Components\TextInput::make('issuer')
                    ->label('الجهة المصدرة')
                    ->required(),
                    
                Forms\Components\TextInput::make('amount')
                    ->label('المبلغ')
                    ->numeric()
                    ->required(),
                    
                Forms\Components\Select::make('currency_id')
                    ->label('العملة')
                    ->relationship('currency', 'code')
                    ->searchable()
                    ->preload(),
                    
                Forms\Components\DatePicker::make('issue_date')
                    ->label('تاريخ الإصدار'),
                    
                Forms\Components\DatePicker::make('validity_date')
                    ->label('تاريخ السريان'),
                    
                Forms\Components\DatePicker::make('expiry_date')
                    ->label('تاريخ الانتهاء')
                    ->required(),
                    
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'سارية',
                        'expired' => 'منتهية',
                        'released' => 'مفرج عنها',
                        'claimed' => 'تم المطالبة بها',
                    ])
                    ->default('active'),
                    
                Forms\Components\DatePicker::make('release_date')
                    ->label('تاريخ الإفراج'),
                    
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2)
                    ->columnSpanFull(),
                    
                Forms\Components\FileUpload::make('document_path')
                    ->label('المستند')
                    ->directory('bonds')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bond_type')
                    ->label('نوع الضمان')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'performance' => 'كفالة حسن التنفيذ',
                        'advance' => 'كفالة الدفعة المقدمة',
                        'retention' => 'كفالة المحتجز',
                        'tender' => 'كفالة العطاء',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'performance' => 'primary',
                        'advance' => 'warning',
                        'retention' => 'info',
                        'tender' => 'success',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('bond_number')
                    ->label('رقم الضمان')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('issuer')
                    ->label('الجهة المصدرة')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('JOD')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('تاريخ الانتهاء')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->days_to_expiry <= 30 ? 'danger' : 'success'),
                    
                Tables\Columns\TextColumn::make('days_to_expiry')
                    ->label('الأيام المتبقية')
                    ->state(fn ($record) => $record->days_to_expiry)
                    ->suffix(' يوم')
                    ->color(fn ($state) => $state <= 30 ? 'danger' : ($state <= 60 ? 'warning' : 'success')),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'active' => 'سارية',
                        'expired' => 'منتهية',
                        'released' => 'مفرج عنها',
                        'claimed' => 'تم المطالبة',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'released' => 'info',
                        'claimed' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bond_type')
                    ->label('نوع الضمان')
                    ->options([
                        'performance' => 'كفالة حسن التنفيذ',
                        'advance' => 'كفالة الدفعة المقدمة',
                        'retention' => 'كفالة المحتجز',
                        'tender' => 'كفالة العطاء',
                    ]),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'سارية',
                        'expired' => 'منتهية',
                        'released' => 'مفرج عنها',
                        'claimed' => 'تم المطالبة',
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
            ]);
    }
}
