<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderPurchaseApprovalResource\Pages;
use App\Models\Tenders\TenderPurchaseApproval;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderPurchaseApprovalResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = TenderPurchaseApproval::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    protected static ?string $navigationLabel = 'موافقات الشراء';
    protected static ?string $modelLabel = 'موافقة شراء';
    protected static ?string $pluralModelLabel = 'موافقات الشراء';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('طلب الموافقة')->schema([
                Forms\Components\Select::make('tender_id')
                    ->label('العطاء')
                    ->relationship('tender', 'name_ar')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\DatePicker::make('request_date')
                    ->label('تاريخ الطلب')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('estimated_cost')
                    ->label('التكلفة التقديرية')
                    ->numeric()
                    ->prefix('JOD'),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options(TenderPurchaseApproval::STATUSES)
                    ->default('pending')
                    ->required(),
            ])->columns(2),

            Forms\Components\Section::make('المبررات')->schema([
                Forms\Components\Textarea::make('justification')
                    ->label('مبررات الشراء')
                    ->rows(4)
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make('قرار الموافقة')
                ->schema([
                    Forms\Components\Textarea::make('approval_notes')
                        ->label('ملاحظات الموافقة')
                        ->rows(3),
                    Forms\Components\Textarea::make('rejection_reason')
                        ->label('سبب الرفض')
                        ->rows(3),
                ])
                ->columns(2)
                ->visible(fn($record) => $record && $record->status !== 'pending'),
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
                Tables\Columns\TextColumn::make('request_date')
                    ->label('تاريخ الطلب')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estimated_cost')
                    ->label('التكلفة')
                    ->money('JOD'),
                Tables\Columns\TextColumn::make('requester.name')
                    ->label('مقدم الطلب'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn($state) => TenderPurchaseApproval::STATUSES[$state] ?? $state)
                    ->color(fn($state) => match($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        'deferred' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('تاريخ القرار')
                    ->dateTime('Y-m-d'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(TenderPurchaseApproval::STATUSES),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('موافقة')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3),
                    ])
                    ->action(fn($record, array $data) => $record->approve(auth()->user(), $data['notes'] ?? null)),
                Tables\Actions\Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('سبب الرفض')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(fn($record, array $data) => $record->reject(auth()->user(), $data['reason'])),
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
            'index' => Pages\ListTenderPurchaseApprovals::route('/'),
            'create' => Pages\CreateTenderPurchaseApproval::route('/create'),
            'edit' => Pages\EditTenderPurchaseApproval::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
