<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderSubmissionResource\Pages;
use App\Models\TenderSubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderSubmissionResource extends Resource
{
    protected static ?string $model = TenderSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';
    
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    
    protected static ?string $modelLabel = 'تقديم عطاء';
    
    protected static ?string $pluralModelLabel = 'تقديمات العطاءات';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات التقديم')
                    ->schema([
                        Forms\Components\Select::make('tender_id')
                            ->label('العطاء')
                            ->relationship('tender', 'title')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\Select::make('vendor_id')
                            ->label('المورد/المقاول')
                            ->relationship('vendor', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\DateTimePicker::make('submission_datetime')
                            ->label('تاريخ ووقت التقديم')
                            ->required(),
                        
                        Forms\Components\TextInput::make('submission_number')
                            ->label('رقم التقديم')
                            ->maxLength(100),
                    ])->columns(2),

                Forms\Components\Section::make('المظاريف')
                    ->schema([
                        Forms\Components\Toggle::make('technical_envelope_received')
                            ->label('استلام المظروف الفني')
                            ->default(false),
                        
                        Forms\Components\Toggle::make('financial_envelope_received')
                            ->label('استلام المظروف المالي')
                            ->default(false),
                        
                        Forms\Components\Toggle::make('bid_security_received')
                            ->label('استلام كفالة الدخول')
                            ->default(false),
                        
                        Forms\Components\TextInput::make('number_of_copies')
                            ->label('عدد النسخ')
                            ->numeric()
                            ->default(1),
                    ])->columns(4),

                Forms\Components\Section::make('التحقق والملاحظات')
                    ->schema([
                        Forms\Components\TextInput::make('received_by')
                            ->label('المستلم')
                            ->maxLength(255),
                        
                        Forms\Components\Toggle::make('is_late_submission')
                            ->label('تقديم متأخر')
                            ->default(false),
                        
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'received' => 'مستلم',
                                'under_review' => 'قيد المراجعة',
                                'accepted' => 'مقبول',
                                'rejected' => 'مرفوض',
                            ])
                            ->default('received'),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('submission_number')
                    ->label('رقم التقديم')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('tender.title')
                    ->label('العطاء')
                    ->searchable()
                    ->limit(25),
                
                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('المورد')
                    ->searchable()
                    ->limit(20),
                
                Tables\Columns\TextColumn::make('submission_datetime')
                    ->label('تاريخ التقديم')
                    ->dateTime()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('technical_envelope_received')
                    ->label('الفني')
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('financial_envelope_received')
                    ->label('المالي')
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('bid_security_received')
                    ->label('الكفالة')
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('is_late_submission')
                    ->label('متأخر')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success'),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'received' => 'مستلم',
                        'under_review' => 'قيد المراجعة',
                        'accepted' => 'مقبول',
                        'rejected' => 'مرفوض',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'received' => 'gray',
                        'under_review' => 'warning',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'received' => 'مستلم',
                        'under_review' => 'قيد المراجعة',
                        'accepted' => 'مقبول',
                        'rejected' => 'مرفوض',
                    ]),
                Tables\Filters\TernaryFilter::make('is_late_submission')
                    ->label('التقديم المتأخر'),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenderSubmissions::route('/'),
            'create' => Pages\CreateTenderSubmission::route('/create'),
            'edit' => Pages\EditTenderSubmission::route('/{record}/edit'),
        ];
    }
}
