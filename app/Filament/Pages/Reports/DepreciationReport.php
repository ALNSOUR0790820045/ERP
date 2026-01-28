<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Models\FixedAssetDepreciation;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class DepreciationReport extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationLabel = 'تقرير الإهلاك';
    
    protected static ?string $title = 'تقرير إهلاك الأصول الثابتة';
    
    protected static ?string $navigationGroup = 'التقارير';
    
    protected static ?int $navigationSort = 1;
    
    protected static string $view = 'filament.pages.reports.depreciation-report';
    
    public ?array $data = [];
    
    public Collection $reportData;
    
    public bool $showReport = false;
    
    public function mount(): void
    {
        $this->form->fill([
            'from_date' => now()->startOfYear()->format('Y-m-d'),
            'to_date' => now()->format('Y-m-d'),
        ]);
        $this->reportData = collect();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معايير التقرير')
                    ->schema([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('من تاريخ')
                            ->required(),
                        
                        Forms\Components\DatePicker::make('to_date')
                            ->label('إلى تاريخ')
                            ->required(),
                        
                        Forms\Components\Select::make('category_id')
                            ->label('تصنيف الأصل')
                            ->options(FixedAssetCategory::pluck('name_ar', 'id'))
                            ->placeholder('جميع التصنيفات'),
                        
                        Forms\Components\Select::make('depreciation_method')
                            ->label('طريقة الإهلاك')
                            ->options([
                                'straight_line' => 'القسط الثابت',
                                'declining_balance' => 'القسط المتناقص',
                                'units_of_production' => 'وحدات الإنتاج',
                            ])
                            ->placeholder('جميع الطرق'),
                    ])
                    ->columns(4),
            ])
            ->statePath('data');
    }
    
    public function generateReport(): void
    {
        $query = FixedAsset::query()
            ->with(['category', 'depreciations'])
            ->where('status', 'active');
        
        if (!empty($this->data['category_id'])) {
            $query->where('category_id', $this->data['category_id']);
        }
        
        if (!empty($this->data['depreciation_method'])) {
            $query->where('depreciation_method', $this->data['depreciation_method']);
        }
        
        $fromDate = Carbon::parse($this->data['from_date']);
        $toDate = Carbon::parse($this->data['to_date']);
        
        $this->reportData = $query->get()->map(function ($asset) use ($fromDate, $toDate) {
            $periodDepreciation = $asset->depreciations()
                ->whereBetween('depreciation_date', [$fromDate, $toDate])
                ->sum('depreciation_amount');
            
            return [
                'asset_code' => $asset->asset_code,
                'asset_name' => $asset->name,
                'category' => $asset->category?->name_ar ?? '-',
                'acquisition_date' => $asset->acquisition_date?->format('Y-m-d'),
                'acquisition_cost' => $asset->acquisition_cost,
                'depreciation_method' => $this->getMethodName($asset->depreciation_method),
                'useful_life' => $asset->useful_life_years . ' سنة',
                'accumulated_depreciation' => $asset->accumulated_depreciation,
                'period_depreciation' => $periodDepreciation,
                'book_value' => $asset->acquisition_cost - $asset->accumulated_depreciation,
            ];
        });
        
        $this->showReport = true;
    }
    
    protected function getMethodName(?string $method): string
    {
        return match($method) {
            'straight_line' => 'القسط الثابت',
            'declining_balance' => 'القسط المتناقص',
            'units_of_production' => 'وحدات الإنتاج',
            default => '-',
        };
    }
    
    public function getTotals(): array
    {
        return [
            'total_cost' => $this->reportData->sum('acquisition_cost'),
            'total_accumulated' => $this->reportData->sum('accumulated_depreciation'),
            'total_period' => $this->reportData->sum('period_depreciation'),
            'total_book_value' => $this->reportData->sum('book_value'),
        ];
    }
    
    public function exportExcel(): void
    {
        // يمكن إضافة تصدير Excel لاحقاً
    }
    
    public function exportPdf(): void
    {
        // يمكن إضافة تصدير PDF لاحقاً
    }
}
