<?php

namespace App\Filament\Resources\TenderResource\Widgets;

use App\Models\Tender;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class TenderTimelineWidget extends Widget
{
    public ?Model $record = null;

    protected static string $view = 'filament.resources.tender-resource.widgets.tender-timeline-widget';

    protected static ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 'full';

    public function getTimeline(): array
    {
        if (!$this->record) {
            return [];
        }

        $tender = $this->record;
        $timeline = [];

        // تاريخ الإنشاء
        $timeline[] = [
            'date' => $tender->created_at,
            'title' => 'تسجيل العطاء',
            'description' => 'تم رصد العطاء رقم ' . $tender->tender_number,
            'icon' => 'heroicon-o-plus-circle',
            'color' => 'info',
        ];

        // تاريخ الإعلان
        if ($tender->publication_date) {
            $timeline[] = [
                'date' => $tender->publication_date,
                'title' => 'الإعلان عن العطاء',
                'description' => 'تم الإعلان الرسمي عن العطاء',
                'icon' => 'heroicon-o-megaphone',
                'color' => 'primary',
            ];
        }

        // زيارة الموقع
        if ($tender->site_visit_date) {
            $timeline[] = [
                'date' => $tender->site_visit_date,
                'title' => 'زيارة الموقع',
                'description' => 'موعد زيارة موقع المشروع',
                'icon' => 'heroicon-o-map-pin',
                'color' => 'warning',
            ];
        }

        // قرار المشاركة
        if ($tender->decision_date) {
            $timeline[] = [
                'date' => $tender->decision_date,
                'title' => $tender->decision === 'go' ? 'قرار المشاركة (Go)' : 'عدم المشاركة (No-Go)',
                'description' => $tender->decision_notes ?? '',
                'icon' => $tender->decision === 'go' ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle',
                'color' => $tender->decision === 'go' ? 'success' : 'danger',
            ];
        }

        // موعد التقديم
        if ($tender->submission_deadline) {
            $timeline[] = [
                'date' => $tender->submission_deadline,
                'title' => 'آخر موعد للتقديم',
                'description' => 'الموعد النهائي لتقديم العطاء',
                'icon' => 'heroicon-o-clock',
                'color' => 'danger',
            ];
        }

        // تاريخ التقديم الفعلي
        if ($tender->submission_date) {
            $timeline[] = [
                'date' => $tender->submission_date,
                'title' => 'تم التقديم',
                'description' => 'تم تقديم العطاء بنجاح',
                'icon' => 'heroicon-o-paper-airplane',
                'color' => 'success',
            ];
        }

        // تاريخ الفتح
        if ($tender->opening_date) {
            $timeline[] = [
                'date' => $tender->opening_date,
                'title' => 'فتح المظاريف',
                'description' => $tender->our_rank ? 'الترتيب: #' . $tender->our_rank : '',
                'icon' => 'heroicon-o-envelope-open',
                'color' => 'info',
            ];
        }

        // تاريخ الترسية
        if ($tender->award_date) {
            $timeline[] = [
                'date' => $tender->award_date,
                'title' => $tender->result?->value === 'won' ? '🏆 الفوز بالعطاء' : 'نتيجة العطاء',
                'description' => $tender->result?->value === 'won' 
                    ? 'تمت الترسية بقيمة ' . number_format($tender->winning_price ?? 0, 0) . ' JOD'
                    : ($tender->loss_reason ?? ''),
                'icon' => $tender->result?->value === 'won' ? 'heroicon-o-trophy' : 'heroicon-o-flag',
                'color' => $tender->result?->value === 'won' ? 'success' : 'warning',
            ];
        }

        // ترتيب حسب التاريخ
        usort($timeline, fn($a, $b) => strtotime($a['date']) - strtotime($b['date']));

        return $timeline;
    }
}
