<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\ProgressCertificate;
use App\Models\Contract;
use App\Models\ContractRetention;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class IPCProgressReport extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static ?string $navigationLabel = 'تقرير المستخلصات';
    
    protected static ?string $title = 'تقرير المستخلصات وتقدم العقود';
    
    protected static ?string $navigationGroup = 'التقارير';
    
    protected static ?int $navigationSort = 5;
    
    protected static string $view = 'filament.pages.reports.ipc-progress-report';
    
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
                        Forms\Components\Select::make('contract_id')
                            ->label('العقد')
                            ->options(Contract::pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('جميع العقود'),
                        
                        Forms\Components\DatePicker::make('from_date')
                            ->label('من تاريخ')
                            ->required(),
                        
                        Forms\Components\DatePicker::make('to_date')
                            ->label('إلى تاريخ')
                            ->required(),
                        
                        Forms\Components\Select::make('status')
                            ->label('حالة المستخلص')
                            ->options([
                                'draft' => 'مسودة',
                                'submitted' => 'مقدم',
                                'approved' => 'معتمد',
                                'paid' => 'مدفوع',
                                'rejected' => 'مرفوض',
                            ])
                            ->placeholder('جميع الحالات'),
                    ])
                    ->columns(4),
            ])
            ->statePath('data');
    }
    
    public function generateReport(): void
    {
        $query = ProgressCertificate::query()
            ->with(['contract', 'retentions']);
        
        if (!empty($this->data['contract_id'])) {
            $query->where('contract_id', $this->data['contract_id']);
        }
        
        if (!empty($this->data['status'])) {
            $query->where('status', $this->data['status']);
        }
        
        $fromDate = Carbon::parse($this->data['from_date']);
        $toDate = Carbon::parse($this->data['to_date']);
        
        $query->whereBetween('certificate_date', [$fromDate, $toDate]);
        
        $this->reportData = $query->get()->map(function ($ipc) {
            $contract = $ipc->contract;
            $totalRetentions = $ipc->retentions->sum('amount');
            
            return [
                'ipc_number' => $ipc->certificate_number,
                'contract_number' => $contract?->contract_number ?? '-',
                'contract_name' => $contract?->name ?? '-',
                'ipc_date' => $ipc->certificate_date?->format('Y-m-d'),
                'period_from' => $ipc->period_from?->format('Y-m-d'),
                'period_to' => $ipc->period_to?->format('Y-m-d'),
                'gross_amount' => $ipc->gross_amount,
                'deductions' => $ipc->deductions_amount ?? 0,
                'retentions' => $totalRetentions,
                'net_amount' => $ipc->net_amount,
                'status' => $this->getStatusName($ipc->status),
                'completion_percentage' => $ipc->completion_percentage ?? 0,
            ];
        });
        
        $this->showReport = true;
    }
    
    protected function getStatusName(?string $status): string
    {
        return match($status) {
            'draft' => 'مسودة',
            'submitted' => 'مقدم',
            'approved' => 'معتمد',
            'paid' => 'مدفوع',
            'rejected' => 'مرفوض',
            default => '-',
        };
    }
    
    public function getTotals(): array
    {
        return [
            'total_gross' => $this->reportData->sum('gross_amount'),
            'total_deductions' => $this->reportData->sum('deductions'),
            'total_retentions' => $this->reportData->sum('retentions'),
            'total_net' => $this->reportData->sum('net_amount'),
            'ipc_count' => $this->reportData->count(),
        ];
    }
    
    public function getContractSummary(): Collection
    {
        return $this->reportData
            ->groupBy('contract_number')
            ->map(function ($items, $contractNumber) {
                return [
                    'contract_number' => $contractNumber,
                    'contract_name' => $items->first()['contract_name'],
                    'ipc_count' => $items->count(),
                    'total_amount' => $items->sum('net_amount'),
                ];
            });
    }
}
