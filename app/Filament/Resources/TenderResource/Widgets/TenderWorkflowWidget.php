<?php

namespace App\Filament\Resources\TenderResource\Widgets;

use App\Models\Tender;
use Filament\Widgets\Widget;

class TenderWorkflowWidget extends Widget
{
    protected static string $view = 'filament.widgets.tender-workflow-widget';
    
    public ?Tender $record = null;
    
    // المراحل الـ 17 للعطاء
    public static array $stages = [
        1 => ['key' => 'discovery', 'name' => 'رصد العطاء', 'icon' => 'magnifying-glass'],
        2 => ['key' => 'document_purchase', 'name' => 'شراء الوثائق', 'icon' => 'shopping-cart'],
        3 => ['key' => 'site_visit', 'name' => 'زيارة الموقع', 'icon' => 'map-pin'],
        4 => ['key' => 'go_nogo', 'name' => 'قرار Go/No-Go', 'icon' => 'check-circle'],
        5 => ['key' => 'technical_prep', 'name' => 'تجهيز العرض الفني', 'icon' => 'document-text'],
        6 => ['key' => 'financial_prep', 'name' => 'تجهيز العرض المالي', 'icon' => 'calculator'],
        7 => ['key' => 'proposal_review', 'name' => 'مراجعة العرض', 'icon' => 'clipboard-document-check'],
        8 => ['key' => 'bond_issuance', 'name' => 'إصدار الكفالة', 'icon' => 'banknotes'],
        9 => ['key' => 'proposal_closure', 'name' => 'إغلاق العرض', 'icon' => 'lock-closed'],
        10 => ['key' => 'submission', 'name' => 'تقديم العرض', 'icon' => 'paper-airplane'],
        11 => ['key' => 'envelope_opening', 'name' => 'فتح المظاريف', 'icon' => 'envelope-open'],
        12 => ['key' => 'clarifications', 'name' => 'الإيضاحات', 'icon' => 'question-mark-circle'],
        13 => ['key' => 'result_tracking', 'name' => 'متابعة النتيجة', 'icon' => 'clock'],
        14 => ['key' => 'award_decision', 'name' => 'قرار الإحالة', 'icon' => 'trophy'],
        15 => ['key' => 'bond_withdrawal', 'name' => 'سحب الكفالة', 'icon' => 'arrow-uturn-left'],
        16 => ['key' => 'contract_signing', 'name' => 'توقيع العقد', 'icon' => 'pencil-square'],
        17 => ['key' => 'project_conversion', 'name' => 'تحويل لمشروع', 'icon' => 'briefcase'],
    ];

    public function getCurrentStage(): int
    {
        if (!$this->record) {
            return 1;
        }
        
        // تحديد المرحلة بناءً على حالة العطاء
        $status = $this->record->status?->value ?? $this->record->status ?? 'new';
        
        return match($status) {
            'new', 'discovered' => 1,
            'documents_purchased' => 2,
            'site_visited' => 3,
            'evaluated', 'go_decision' => 4,
            'no_go_decision' => 0, // تم إيقافه
            'technical_preparation' => 5,
            'financial_preparation' => 6,
            'under_review' => 7,
            'bond_issued' => 8,
            'closed' => 9,
            'submitted' => 10,
            'opened' => 11,
            'clarifications' => 12,
            'pending_result' => 13,
            'won' => 14,
            'lost' => 15,
            'contract_signed' => 16,
            'converted_to_project' => 17,
            default => 1,
        };
    }

    public function getStageStatus(int $stageNumber): string
    {
        $currentStage = $this->getCurrentStage();
        
        if ($currentStage === 0) {
            return 'stopped';
        }
        
        if ($stageNumber < $currentStage) {
            return 'completed';
        } elseif ($stageNumber === $currentStage) {
            return 'current';
        } else {
            return 'pending';
        }
    }
}
