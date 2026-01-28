<?php

namespace App\Filament\Widgets;

use Filament\Widgets\TableWidget;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\MaterialStock;
use App\Models\Material;
use Illuminate\Database\Eloquent\Builder;

class InventoryAlertsWidget extends TableWidget
{
    protected static ?int $sort = 3;
    
    protected int|string|array $columnSpan = 'full';
    
    public function getHeading(): ?string
    {
        return 'تنبيهات المخزون';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MaterialStock::query()
                    ->with(['material', 'warehouse'])
                    ->whereHas('material', function (Builder $query) {
                        $query->whereColumn('material_stocks.quantity', '<=', 'materials.reorder_level');
                    })
                    ->orWhere('quantity', '<=', 0)
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('material.name')
                    ->label('المادة')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('المستودع')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('quantity')
                    ->label('الكمية المتوفرة')
                    ->numeric()
                    ->color(fn ($state) => $state <= 0 ? 'danger' : 'warning'),
                
                Tables\Columns\TextColumn::make('material.reorder_level')
                    ->label('حد إعادة الطلب')
                    ->numeric(),
                
                Tables\Columns\TextColumn::make('material.unit.name')
                    ->label('الوحدة'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->getStateUsing(function ($record) {
                        if ($record->quantity <= 0) {
                            return 'نفاد';
                        }
                        return 'منخفض';
                    })
                    ->colors([
                        'danger' => 'نفاد',
                        'warning' => 'منخفض',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('create_pr')
                    ->label('طلب شراء')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('primary')
                    ->url(fn ($record) => route('filament.admin.resources.purchase-requests.create', [
                        'material_id' => $record->material_id,
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('لا توجد تنبيهات')
            ->emptyStateDescription('جميع المواد ضمن مستويات المخزون الآمنة')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
