<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class BimProject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'ifc_file_path',
        'revit_file_path',
        'navisworks_file_path',
        'coordination_model_path',
        'status',
        'version',
        'lod_level', // Level of Development: 100, 200, 300, 350, 400, 500
        'coordinate_system',
        'units',
        'total_elements',
        'synced_elements',
        'last_sync_at',
        'sync_status',
        'sync_errors',
        'model_origin_x',
        'model_origin_y',
        'model_origin_z',
        'model_rotation',
        '4d_enabled',
        '5d_enabled',
        'clash_detection_enabled',
        'auto_quantity_sync',
        'settings',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
        'sync_errors' => 'array',
        'settings' => 'array',
        'metadata' => 'array',
        'model_origin_x' => 'decimal:6',
        'model_origin_y' => 'decimal:6',
        'model_origin_z' => 'decimal:6',
        'model_rotation' => 'decimal:4',
        '4d_enabled' => 'boolean',
        '5d_enabled' => 'boolean',
        'clash_detection_enabled' => 'boolean',
        'auto_quantity_sync' => 'boolean',
    ];

    // Statuses
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_SYNCING = 'syncing';
    const STATUS_ERROR = 'error';
    const STATUS_ARCHIVED = 'archived';

    // Sync Statuses
    const SYNC_PENDING = 'pending';
    const SYNC_IN_PROGRESS = 'in_progress';
    const SYNC_COMPLETED = 'completed';
    const SYNC_FAILED = 'failed';

    // LOD Levels (Level of Development)
    const LOD_100 = 100; // Conceptual
    const LOD_200 = 200; // Approximate Geometry
    const LOD_300 = 300; // Precise Geometry
    const LOD_350 = 350; // Construction Documentation
    const LOD_400 = 400; // Fabrication
    const LOD_500 = 500; // As-Built

    // ===== العلاقات =====

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function elements()
    {
        return $this->hasMany(BimElement::class);
    }

    public function clashDetections()
    {
        return $this->hasMany(BimClashDetection::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ===== Scopes =====

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeWith4D($query)
    {
        return $query->where('4d_enabled', true);
    }

    public function scopeWith5D($query)
    {
        return $query->where('5d_enabled', true);
    }

    public function scopeNeedingSync($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('last_sync_at')
              ->orWhere('sync_status', self::SYNC_FAILED);
        });
    }

    // ===== Accessors =====

    public function getSyncProgressAttribute()
    {
        if ($this->total_elements == 0) {
            return 0;
        }
        return round(($this->synced_elements / $this->total_elements) * 100, 2);
    }

    public function getIfcFileUrlAttribute()
    {
        return $this->ifc_file_path ? Storage::url($this->ifc_file_path) : null;
    }

    public function getRevitFileUrlAttribute()
    {
        return $this->revit_file_path ? Storage::url($this->revit_file_path) : null;
    }

    public function getActiveClashCountAttribute()
    {
        return $this->clashDetections()->active()->count();
    }

    public function getLodLevelNameAttribute()
    {
        $levels = [
            100 => 'LOD 100 - مفاهيمي',
            200 => 'LOD 200 - هندسة تقريبية',
            300 => 'LOD 300 - هندسة دقيقة',
            350 => 'LOD 350 - وثائق البناء',
            400 => 'LOD 400 - التصنيع',
            500 => 'LOD 500 - كما بُني',
        ];
        return $levels[$this->lod_level] ?? 'غير محدد';
    }

    // ===== Methods =====

    /**
     * استيراد ملف IFC وتحليله
     */
    public function importIfcFile(string $filePath): array
    {
        $this->update([
            'ifc_file_path' => $filePath,
            'status' => self::STATUS_SYNCING,
            'sync_status' => self::SYNC_IN_PROGRESS,
        ]);

        try {
            // تحليل ملف IFC
            $elements = $this->parseIfcFile($filePath);
            
            $this->update([
                'total_elements' => count($elements),
                'synced_elements' => 0,
            ]);

            // إنشاء العناصر
            foreach ($elements as $elementData) {
                $this->elements()->create($elementData);
                $this->increment('synced_elements');
            }

            $this->update([
                'status' => self::STATUS_ACTIVE,
                'sync_status' => self::SYNC_COMPLETED,
                'last_sync_at' => now(),
                'sync_errors' => null,
            ]);

            return [
                'success' => true,
                'elements_count' => count($elements),
            ];

        } catch (\Exception $e) {
            $this->update([
                'status' => self::STATUS_ERROR,
                'sync_status' => self::SYNC_FAILED,
                'sync_errors' => [$e->getMessage()],
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * تحليل ملف IFC (Placeholder - يحتاج مكتبة IFC)
     */
    protected function parseIfcFile(string $filePath): array
    {
        // هنا يتم استخدام مكتبة IFC لتحليل الملف
        // مثل: php-ifc-tools أو استدعاء أداة خارجية
        return [];
    }

    /**
     * ربط العناصر مع جدول الكميات
     */
    public function linkElementsToBOQ(): array
    {
        $linked = 0;
        $notLinked = [];

        foreach ($this->elements()->unlinked()->get() as $element) {
            // محاولة الربط التلقائي بناءً على الكود أو الوصف
            $boqItem = $this->findMatchingBOQItem($element);
            
            if ($boqItem) {
                $element->update([
                    'boq_item_id' => $boqItem->id,
                    'is_linked' => true,
                ]);
                $linked++;
            } else {
                $notLinked[] = $element->element_id;
            }
        }

        return [
            'linked' => $linked,
            'not_linked' => $notLinked,
        ];
    }

    /**
     * البحث عن عنصر BOQ مطابق
     */
    protected function findMatchingBOQItem(BimElement $element)
    {
        // البحث بناءً على الكود أو الوصف
        return null; // يحتاج تنفيذ حسب البنية
    }

    /**
     * ربط العناصر مع مهام الجدول الزمني (4D)
     */
    public function linkElementsToSchedule(): array
    {
        if (!$this->{'4d_enabled'}) {
            return ['error' => '4D غير مفعل لهذا المشروع'];
        }

        $linked = 0;

        foreach ($this->elements()->unscheduled()->get() as $element) {
            $task = $this->findMatchingTask($element);
            
            if ($task) {
                $element->update([
                    'gantt_task_id' => $task->id,
                    'scheduled_start' => $task->start_date,
                    'scheduled_end' => $task->end_date,
                ]);
                $linked++;
            }
        }

        return ['linked' => $linked];
    }

    /**
     * البحث عن مهمة مطابقة
     */
    protected function findMatchingTask(BimElement $element)
    {
        return null; // يحتاج تنفيذ حسب البنية
    }

    /**
     * تشغيل كشف التصادمات
     */
    public function runClashDetection(): array
    {
        if (!$this->clash_detection_enabled) {
            return ['error' => 'كشف التصادمات غير مفعل'];
        }

        $clashes = [];
        $elements = $this->elements()->active()->get();

        // كشف التصادمات بين العناصر
        foreach ($elements as $i => $element1) {
            foreach ($elements->slice($i + 1) as $element2) {
                if ($this->elementsClash($element1, $element2)) {
                    $clash = $this->clashDetections()->create([
                        'element1_id' => $element1->id,
                        'element2_id' => $element2->id,
                        'clash_type' => $this->determineClashType($element1, $element2),
                        'severity' => $this->calculateClashSeverity($element1, $element2),
                        'status' => 'new',
                        'location_x' => ($element1->center_x + $element2->center_x) / 2,
                        'location_y' => ($element1->center_y + $element2->center_y) / 2,
                        'location_z' => ($element1->center_z + $element2->center_z) / 2,
                    ]);
                    $clashes[] = $clash;
                }
            }
        }

        return [
            'clashes_found' => count($clashes),
            'clashes' => $clashes,
        ];
    }

    /**
     * فحص تصادم عنصرين
     */
    protected function elementsClash(BimElement $e1, BimElement $e2): bool
    {
        // Bounding box intersection check
        return $e1->min_x <= $e2->max_x && $e1->max_x >= $e2->min_x &&
               $e1->min_y <= $e2->max_y && $e1->max_y >= $e2->min_y &&
               $e1->min_z <= $e2->max_z && $e1->max_z >= $e2->min_z;
    }

    /**
     * تحديد نوع التصادم
     */
    protected function determineClashType(BimElement $e1, BimElement $e2): string
    {
        if ($e1->discipline !== $e2->discipline) {
            return 'hard'; // تصادم بين تخصصات مختلفة
        }
        return 'soft'; // تصادم ضمن نفس التخصص
    }

    /**
     * حساب شدة التصادم
     */
    protected function calculateClashSeverity(BimElement $e1, BimElement $e2): string
    {
        // حسب حجم التداخل
        return 'medium';
    }

    /**
     * حساب الكميات من النموذج (5D)
     */
    public function calculateQuantities(): array
    {
        if (!$this->{'5d_enabled'}) {
            return ['error' => '5D غير مفعل لهذا المشروع'];
        }

        $quantities = [];

        foreach ($this->elements()->with('boqItem')->get() as $element) {
            $quantities[$element->element_type] = ($quantities[$element->element_type] ?? 0) + $element->calculated_quantity;
        }

        return $quantities;
    }

    /**
     * مقارنة الكميات المحسوبة مع BOQ
     */
    public function compareQuantitiesWithBOQ(): array
    {
        $comparison = [];

        foreach ($this->elements()->linked()->with('boqItem')->get() as $element) {
            if ($element->boqItem) {
                $variance = $element->calculated_quantity - $element->boqItem->quantity;
                $variancePercent = $element->boqItem->quantity > 0 
                    ? ($variance / $element->boqItem->quantity) * 100 
                    : 0;

                $comparison[] = [
                    'element_id' => $element->element_id,
                    'boq_item' => $element->boqItem->description,
                    'bim_quantity' => $element->calculated_quantity,
                    'boq_quantity' => $element->boqItem->quantity,
                    'variance' => $variance,
                    'variance_percent' => round($variancePercent, 2),
                ];
            }
        }

        return $comparison;
    }

    /**
     * تصدير تقرير BIM
     */
    public function generateReport(): array
    {
        return [
            'project' => $this->name,
            'version' => $this->version,
            'lod_level' => $this->lod_level_name,
            'total_elements' => $this->total_elements,
            'synced_elements' => $this->synced_elements,
            'sync_progress' => $this->sync_progress . '%',
            'active_clashes' => $this->active_clash_count,
            '4d_enabled' => $this->{'4d_enabled'},
            '5d_enabled' => $this->{'5d_enabled'},
            'elements_by_type' => $this->elements()->selectRaw('element_type, count(*) as count')
                ->groupBy('element_type')->pluck('count', 'element_type'),
            'elements_by_discipline' => $this->elements()->selectRaw('discipline, count(*) as count')
                ->groupBy('discipline')->pluck('count', 'discipline'),
        ];
    }
}
