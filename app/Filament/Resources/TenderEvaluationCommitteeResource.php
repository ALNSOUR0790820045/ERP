<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderEvaluationCommitteeResource\Pages;
use App\Models\TenderEvaluationCommittee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderEvaluationCommitteeResource extends Resource
{
    protected static ?string $model = TenderEvaluationCommittee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    
    protected static ?string $modelLabel = 'لجنة تقييم';
    
    protected static ?string $pluralModelLabel = 'لجان التقييم';

    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات اللجنة')
                    ->schema([
                        Forms\Components\Select::make('tender_id')
                            ->label('العطاء')
                            ->relationship('tender', 'title')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\Select::make('committee_type')
                            ->label('نوع اللجنة')
                            ->options([
                                'opening' => 'لجنة فتح العروض',
                                'technical' => 'لجنة التقييم الفني',
                                'financial' => 'لجنة التقييم المالي',
                                'award' => 'لجنة الإحالة',
                            ])
                            ->required(),
                        
                        Forms\Components\DatePicker::make('formation_date')
                            ->label('تاريخ التشكيل')
                            ->required(),
                        
                        Forms\Components\TextInput::make('decision_number')
                            ->label('رقم قرار التشكيل')
                            ->maxLength(100),
                    ])->columns(2),

                Forms\Components\Section::make('معلومات العضو')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('العضو')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\TextInput::make('member_name')
                            ->label('اسم العضو')
                            ->maxLength(255)
                            ->helperText('في حالة عدم وجود المستخدم في النظام'),
                        
                        Forms\Components\Select::make('role')
                            ->label('الدور')
                            ->options([
                                'chairman' => 'رئيس اللجنة',
                                'member' => 'عضو',
                                'secretary' => 'أمين السر',
                                'technical_expert' => 'خبير فني',
                                'financial_expert' => 'خبير مالي',
                                'legal_advisor' => 'مستشار قانوني',
                            ])
                            ->required(),
                        
                        Forms\Components\TextInput::make('organization')
                            ->label('الجهة')
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('position')
                            ->label('المنصب')
                            ->maxLength(255),
                    ])->columns(3),

                Forms\Components\Section::make('ملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tender.title')
                    ->label('العطاء')
                    ->searchable()
                    ->limit(25),
                
                Tables\Columns\TextColumn::make('committee_type')
                    ->label('نوع اللجنة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'opening' => 'فتح العروض',
                        'technical' => 'التقييم الفني',
                        'financial' => 'التقييم المالي',
                        'award' => 'الإحالة',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'opening' => 'info',
                        'technical' => 'warning',
                        'financial' => 'success',
                        'award' => 'primary',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('member_name')
                    ->label('العضو')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('role')
                    ->label('الدور')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'chairman' => 'رئيس',
                        'member' => 'عضو',
                        'secretary' => 'أمين سر',
                        'technical_expert' => 'خبير فني',
                        'financial_expert' => 'خبير مالي',
                        'legal_advisor' => 'مستشار قانوني',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('organization')
                    ->label('الجهة')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('formation_date')
                    ->label('تاريخ التشكيل')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('decision_number')
                    ->label('رقم القرار')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('committee_type')
                    ->label('نوع اللجنة')
                    ->options([
                        'opening' => 'فتح العروض',
                        'technical' => 'التقييم الفني',
                        'financial' => 'التقييم المالي',
                        'award' => 'الإحالة',
                    ]),
                Tables\Filters\SelectFilter::make('role')
                    ->label('الدور')
                    ->options([
                        'chairman' => 'رئيس',
                        'member' => 'عضو',
                        'secretary' => 'أمين سر',
                    ]),
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
            'index' => Pages\ListTenderEvaluationCommittees::route('/'),
            'create' => Pages\CreateTenderEvaluationCommittee::route('/create'),
            'edit' => Pages\EditTenderEvaluationCommittee::route('/{record}/edit'),
        ];
    }
}
