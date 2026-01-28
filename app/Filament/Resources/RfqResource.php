<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RfqResource\Pages;
use App\Models\Rfq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RfqResource extends Resource
{
    protected static ?string $model = Rfq::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'المشتريات';
    protected static ?string $modelLabel = 'طلب عروض أسعار';
    protected static ?string $pluralModelLabel = 'طلبات عروض الأسعار';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات طلب العروض')
                ->schema([
                    Forms\Components\TextInput::make('rfq_number')
                        ->label('رقم RFQ')
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\DatePicker::make('rfq_date')
                        ->label('تاريخ الطلب')
                        ->required()
                        ->default(now()),
                    Forms\Components\TextInput::make('subject')
                        ->label('الموضوع')
                        ->required()
                        ->maxLength(500)
                        ->columnSpanFull(),
                    Forms\Components\Select::make('rfq_type')
                        ->label('نوع الطلب')
                        ->options([
                            'rfq' => 'طلب عروض أسعار (RFQ)',
                            'rfp' => 'طلب عروض فنية ومالية (RFP)',
                            'rfi' => 'طلب معلومات (RFI)',
                        ])
                        ->default('rfq')
                        ->required(),
                    Forms\Components\Select::make('project_id')
                        ->label('المشروع')
                        ->relationship('project', 'name_ar')
                        ->searchable()
                        ->preload(),
                    Forms\Components\DateTimePicker::make('deadline')
                        ->label('الموعد النهائي')
                        ->required(),
                    Forms\Components\TextInput::make('validity_days')
                        ->label('صلاحية العرض (أيام)')
                        ->numeric()
                        ->default(30),
                    Forms\Components\DatePicker::make('delivery_required_date')
                        ->label('تاريخ التسليم المطلوب'),
                    Forms\Components\TextInput::make('delivery_location')
                        ->label('مكان التسليم')
                        ->maxLength(255),
                    Forms\Components\Textarea::make('payment_terms')
                        ->label('شروط الدفع')
                        ->rows(2),
                    Forms\Components\Textarea::make('terms_conditions')
                        ->label('الشروط والأحكام')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'draft' => 'مسودة',
                            'sent' => 'مرسل',
                            'closed' => 'مغلق',
                            'awarded' => 'مُرسى',
                            'cancelled' => 'ملغى',
                        ])
                        ->default('draft'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rfq_number')
                    ->label('رقم RFQ')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('الموضوع')
                    ->limit(40)
                    ->searchable(),
                Tables\Columns\TextColumn::make('rfq_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'rfq' => 'RFQ',
                        'rfp' => 'RFP',
                        'rfi' => 'RFI',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('project.name_ar')
                    ->label('المشروع')
                    ->limit(20),
                Tables\Columns\TextColumn::make('deadline')
                    ->label('الموعد النهائي')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'draft' => 'gray',
                        'sent' => 'info',
                        'closed' => 'warning',
                        'awarded' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'مسودة',
                        'sent' => 'مرسل',
                        'closed' => 'مغلق',
                        'awarded' => 'مُرسى',
                        'cancelled' => 'ملغى',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'sent' => 'مرسل',
                        'closed' => 'مغلق',
                        'awarded' => 'مُرسى',
                        'cancelled' => 'ملغى',
                    ]),
                Tables\Filters\SelectFilter::make('rfq_type')
                    ->label('النوع')
                    ->options([
                        'rfq' => 'RFQ',
                        'rfp' => 'RFP',
                        'rfi' => 'RFI',
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRfqs::route('/'),
            'create' => Pages\CreateRfq::route('/create'),
            'edit' => Pages\EditRfq::route('/{record}/edit'),
        ];
    }
}
