<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\MaterialStock;
use App\Models\Material;
use App\Models\Warehouse;
use App\Models\MaterialCategory;
use Illuminate\Support\Collection;

class InventoryValuationReport extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    
    protected static ?string $navigationLabel = 'تقرير تقييم المخزون';
    
    protected static ?string $title = 'تقرير تقييم المخزون';
    
    protected static ?string $navigationGroup = 'التقارير';
    
    protected static ?int $navigationSort = 6;
    
    protected static string $view = 'filament.pages.reports.inventory-valuation-report';
    
    public ?array $data = [];
    
    public Collection $reportData;
    
    public bool $showReport = false;
    
    public function mount(): void
    {
        $this->form->fill([
            'valuation_method' => 'weighted_average',
        ]);
        $this->reportData = collect();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معايير التقرير')
                    ->schema([
                        Forms\Components\Select::make('warehouse_id')
                            ->label('المستودع')
                            ->options(Warehouse::where('is_active', true)->pluck('name', 'id'))
                            ->placeholder('جميع المستودعات'),
                        
                        Forms\Components\Select::make('category_id')
                            ->label('تصنيف المادة')
                            ->options(MaterialCategory::pluck('name', 'id'))
                            ->placeholder('جميع التصنيفات'),
                        
                        Forms\Components\Select::make('valuation_method')
                            ->label('طريقة التقييم')
                            ->options([
                                'weighted_average' => 'المتوسط المرجح',
                                'fifo' => 'الوارد أولاً صادر أولاً (FIFO)',
                                'lifo' => 'الوارد أخيراً صادر أولاً (LIFO)',
                            ])
                            ->required(),
                        
                        Forms\Components\Toggle::make('show_zero_stock')
                            ->label('إظهار المواد بدون رصيد')
                            ->default(false),
                    ])
                    ->columns(4),
            ])
            ->statePath('data');
    }
    
    public function generateReport(): void
    {
        $query = MaterialStock::query()
            ->with(['material', 'material.category', 'material.unit', 'warehouse']);
        
        if (!empty($this->data['warehouse_id'])) {
            $query->where('warehouse_id', $this->data['warehouse_id']);
        }
        
        if (!empty($this->data['category_id'])) {
            $query->whereHas('material', function ($q) {
                $q->where('category_id', $this->data['category_id']);
            });
        }
        
        if (empty($this->data['show_zero_stock'])) {
            $query->where('quantity', '>', 0);
        }
        
        $this->reportData = $query->get()->map(function ($stock) {
            $material = $stock->material;
            $unitCost = $stock->average_cost ?? $material->last_purchase_price ?? 0;
            $totalValue = $stock->quantity * $unitCost;
            
            return [
                'material_code' => $material?->code ?? '-',
                'material_name' => $material?->name ?? '-',
                'category' => $material?->category?->name ?? '-',
                'warehouse' => $stock->warehouse?->name ?? '-',
                'unit' => $material?->unit?->name ?? '-',
                'quantity' => $stock->quantity,
                'unit_cost' => $unitCost,
                'total_value' => $totalValue,
                'reorder_level' => $material?->reorder_level ?? 0,
                'stock_status' => $this->getStockStatus($stock->quantity, $material?->reorder_level ?? 0),
            ];
        });
        
        $this->showReport = true;
    }
    
    protected function getStockStatus(float $quantity, float $reorderLevel): string
    {
        if ($quantity <= 0) {
            return 'نفاد';
        } elseif ($quantity <= $reorderLevel) {
            return 'منخفض';
        } else {
            return 'متوفر';
        }
    }
    
    public function getTotals(): array
    {
        return [
            'total_items' => $this->reportData->count(),
            'total_quantity' => $this->reportData->sum('quantity'),
            'total_value' => $this->reportData->sum('total_value'),
            'low_stock_count' => $this->reportData->where('stock_status', 'منخفض')->count(),
            'out_of_stock_count' => $this->reportData->where('stock_status', 'نفاد')->count(),
        ];
    }
    
    public function getCategorySummary(): Collection
    {
        return $this->reportData
            ->groupBy('category')
            ->map(function ($items, $category) {
                return [
                    'category' => $category,
                    'item_count' => $items->count(),
                    'total_value' => $items->sum('total_value'),
                ];
            })
            ->sortByDesc('total_value');
    }
}
