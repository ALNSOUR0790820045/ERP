<?php

namespace App\Models\DocumentManagement;

use App\Models\Document;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Document Analytics Model
 * تحليلات ومقاييس المستندات
 */
class DocumentAnalytic extends Model
{
    protected $fillable = [
        'document_id',
        'project_id',
        'metric_type',
        'period_type',
        'period_date',
        'count',
        'value',
        'breakdown',
        'metadata',
    ];

    protected $casts = [
        'period_date' => 'date',
        'breakdown' => 'array',
        'metadata' => 'array',
        'count' => 'integer',
        'value' => 'decimal:2',
    ];

    // Metric Types
    const METRIC_VIEWS = 'views';
    const METRIC_DOWNLOADS = 'downloads';
    const METRIC_EDITS = 'edits';
    const METRIC_REVISIONS = 'revisions';
    const METRIC_COMMENTS = 'comments';
    const METRIC_SHARES = 'shares';
    const METRIC_APPROVALS = 'approvals';
    const METRIC_REJECTIONS = 'rejections';
    const METRIC_PROCESSING_TIME = 'processing_time';

    // Period Types
    const PERIOD_HOURLY = 'hourly';
    const PERIOD_DAILY = 'daily';
    const PERIOD_WEEKLY = 'weekly';
    const PERIOD_MONTHLY = 'monthly';

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Scopes
    public function scopeForDocument($query, int $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeForMetric($query, string $metricType)
    {
        return $query->where('metric_type', $metricType);
    }

    public function scopeForPeriod($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_date', [$startDate, $endDate]);
    }

    // Helper Methods
    public static function recordView(Document $document): void
    {
        static::incrementMetric($document, self::METRIC_VIEWS);
    }

    public static function recordDownload(Document $document): void
    {
        static::incrementMetric($document, self::METRIC_DOWNLOADS);
    }

    public static function recordEdit(Document $document): void
    {
        static::incrementMetric($document, self::METRIC_EDITS);
    }

    public static function recordRevision(Document $document): void
    {
        static::incrementMetric($document, self::METRIC_REVISIONS);
    }

    protected static function incrementMetric(Document $document, string $metricType): void
    {
        $today = now()->toDateString();
        
        $analytic = static::firstOrCreate([
            'document_id' => $document->id,
            'project_id' => $document->project_id,
            'metric_type' => $metricType,
            'period_type' => self::PERIOD_DAILY,
            'period_date' => $today,
        ], [
            'count' => 0,
        ]);

        $analytic->increment('count');
    }

    public static function getDocumentStats(int $documentId, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        return [
            'views' => static::forDocument($documentId)
                ->forMetric(self::METRIC_VIEWS)
                ->where('period_date', '>=', $startDate)
                ->sum('count'),
            'downloads' => static::forDocument($documentId)
                ->forMetric(self::METRIC_DOWNLOADS)
                ->where('period_date', '>=', $startDate)
                ->sum('count'),
            'edits' => static::forDocument($documentId)
                ->forMetric(self::METRIC_EDITS)
                ->where('period_date', '>=', $startDate)
                ->sum('count'),
            'revisions' => static::forDocument($documentId)
                ->forMetric(self::METRIC_REVISIONS)
                ->where('period_date', '>=', $startDate)
                ->sum('count'),
        ];
    }

    public static function getProjectStats(int $projectId, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $stats = static::forProject($projectId)
            ->where('period_date', '>=', $startDate)
            ->selectRaw('metric_type, SUM(count) as total')
            ->groupBy('metric_type')
            ->pluck('total', 'metric_type')
            ->toArray();

        return [
            'total_views' => $stats[self::METRIC_VIEWS] ?? 0,
            'total_downloads' => $stats[self::METRIC_DOWNLOADS] ?? 0,
            'total_edits' => $stats[self::METRIC_EDITS] ?? 0,
            'total_revisions' => $stats[self::METRIC_REVISIONS] ?? 0,
            'total_comments' => $stats[self::METRIC_COMMENTS] ?? 0,
        ];
    }

    public static function getTrendData(int $projectId, string $metricType, int $days = 30): array
    {
        return static::forProject($projectId)
            ->forMetric($metricType)
            ->forPeriod(self::PERIOD_DAILY)
            ->where('period_date', '>=', now()->subDays($days))
            ->orderBy('period_date')
            ->get(['period_date', 'count'])
            ->map(fn ($item) => [
                'date' => $item->period_date->format('Y-m-d'),
                'count' => $item->count,
            ])
            ->toArray();
    }

    public static function getTopDocuments(int $projectId, string $metricType, int $limit = 10): array
    {
        return static::forProject($projectId)
            ->forMetric($metricType)
            ->selectRaw('document_id, SUM(count) as total')
            ->groupBy('document_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->with('document:id,document_number,title')
            ->get()
            ->map(fn ($item) => [
                'document' => $item->document,
                'total' => $item->total,
            ])
            ->toArray();
    }

    public static function aggregateDaily(): void
    {
        // Aggregate hourly data into daily
        $yesterday = now()->subDay()->toDateString();
        
        $hourlyData = static::forPeriod(self::PERIOD_HOURLY)
            ->whereDate('period_date', $yesterday)
            ->selectRaw('document_id, project_id, metric_type, DATE(period_date) as day, SUM(count) as total')
            ->groupBy('document_id', 'project_id', 'metric_type', DB::raw('DATE(period_date)'))
            ->get();

        foreach ($hourlyData as $data) {
            static::updateOrCreate([
                'document_id' => $data->document_id,
                'project_id' => $data->project_id,
                'metric_type' => $data->metric_type,
                'period_type' => self::PERIOD_DAILY,
                'period_date' => $data->day,
            ], [
                'count' => $data->total,
            ]);
        }
    }
}
