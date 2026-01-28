<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\ContractRetention;
use App\Models\Contract;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class RetentionsReport extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'تقرير المحتجزات';
    
    protected static ?string $title = 'تقرير محتجزات العقود';
    
    protected static ?string $navigationGroup = 'التقارير';
    
    protected static ?int $navigationSort = 2;
    
    protected static string $view = 'filament.pages.reports.retentions-report';
    
    public ?array $data = [];
    
    public Collection $reportData;
    
    public bool $showReport = false;
    
    public function mount(): void
    {
        $this->form->fill([
            'status' => null,
        ]);
        $this->reportData = collect();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معايير التقرير')
                    ->schema([
                        Forms\Components\Select::make('contract_id')
                            ->label('العقد')
                            ->options(Contract::pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('جميع العقود'),
                        
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'held' => 'محتجزة',
                                'pending_release' => 'بانتظار الإفراج',
                                'released' => 'مُفرج عنها',
                                'forfeited' => 'مصادرة',
                            ])
                            ->placeholder('جميع الحالات'),
                        
                        Forms\Components\Select::make('retention_type')
                            ->label('نوع المحتجز')
                            ->options([
                                'performance' => 'ضمان حسن التنفيذ',
                                'maintenance' => 'ضمان الصيانة',
                                'advance' => 'استرداد دفعة مقدمة',
                            ])
                            ->placeholder('جميع الأنواع'),
                        
                        Forms\Components\DatePicker::make('due_before')
                            ->label('تاريخ الاستحقاق قبل'),
                    ])
                    ->columns(4),
            ])
            ->statePath('data');
    }
    
    public function generateReport(): void
    {
        $query = ContractRetention::query()
            ->with(['contract', 'progressCertificate']);
        
        if (!empty($this->data['contract_id'])) {
            $query->where('contract_id', $this->data['contract_id']);
        }
        
        if (!empty($this->data['status'])) {
            $query->where('status', $this->data['status']);
        }
        
        if (!empty($this->data['retention_type'])) {
            $query->where('retention_type', $this->data['retention_type']);
        }
        
        if (!empty($this->data['due_before'])) {
            $query->where('release_date', '<=', $this->data['due_before']);
        }
        
        $this->reportData = $query->get()->map(function ($retention) {
            return [
                'contract_number' => $retention->contract?->contract_number ?? '-',
                'contract_name' => $retention->contract?->name ?? '-',
                'retention_type' => $this->getTypeName($retention->retention_type),
                'ipc_number' => $retention->progressCertificate?->certificate_number ?? '-',
                'amount' => $retention->amount,
                'percentage' => $retention->percentage . '%',
                'status' => $this->getStatusName($retention->status),
                'release_date' => $retention->release_date?->format('Y-m-d') ?? '-',
                'days_to_release' => $retention->release_date 
                    ? now()->diffInDays($retention->release_date, false) 
                    : null,
            ];
        });
        
        $this->showReport = true;
    }
    
    protected function getTypeName(?string $type): string
    {
        return match($type) {
            'performance' => 'ضمان حسن التنفيذ',
            'maintenance' => 'ضمان الصيانة',
            'advance' => 'استرداد دفعة مقدمة',
            default => '-',
        };
    }
    
    protected function getStatusName(?string $status): string
    {
        return match($status) {
            'held' => 'محتجزة',
            'pending_release' => 'بانتظار الإفراج',
            'released' => 'مُفرج عنها',
            'forfeited' => 'مصادرة',
            default => '-',
        };
    }
    
    public function getTotals(): array
    {
        $byStatus = $this->reportData->groupBy('status');
        
        return [
            'total_amount' => $this->reportData->sum('amount'),
            'held_amount' => $byStatus->get('محتجزة')?->sum('amount') ?? 0,
            'pending_amount' => $byStatus->get('بانتظار الإفراج')?->sum('amount') ?? 0,
            'released_amount' => $byStatus->get('مُفرج عنها')?->sum('amount') ?? 0,
        ];
    }
}
