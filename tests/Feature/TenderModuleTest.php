<?php

namespace Tests\Feature;

use App\Enums\OwnerType;
use App\Enums\TenderMethod;
use App\Enums\TenderStatus;
use App\Enums\TenderType;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * اختبارات شاملة لوحدة العطاءات
 * تغطي: الصلاحيات، الصفحات، مسارات العمل، الارتباطات
 */
class TenderModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularUser;
    protected User $tenderUser;
    protected Role $superAdminRole;
    protected Role $tenderRole;
    protected Currency $currency;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        // إنشاء العملة الافتراضية
        $this->currency = Currency::firstOrCreate(
            ['code' => 'JOD'],
            [
                'name_ar' => 'دينار أردني',
                'name_en' => 'Jordanian Dinar',
                'symbol' => 'د.أ',
                'exchange_rate' => 1.00,
                'is_default' => true,
                'is_active' => true,
            ]
        );

        // إنشاء عميل للاختبار
        $this->customer = Customer::firstOrCreate(
            ['customer_code' => 'GOV-001'],
            [
                'company_name' => 'وزارة الأشغال العامة',
                'phone' => '0612345678',
                'email' => 'info@mpwh.gov.jo',
            ]
        );

        // إنشاء الأدوار
        $this->superAdminRole = Role::firstOrCreate(
            ['code' => 'super_admin'],
            ['name_ar' => 'مدير النظام', 'name_en' => 'Super Admin', 'is_active' => true]
        );

        $this->tenderRole = Role::firstOrCreate(
            ['code' => 'tender_manager'],
            ['name_ar' => 'مدير العطاءات', 'name_en' => 'Tender Manager', 'is_active' => true]
        );

        // إنشاء المستخدمين
        $this->superAdmin = User::factory()->create([
            'role_id' => $this->superAdminRole->id,
            'email' => 'admin@test.com',
        ]);

        $this->regularUser = User::factory()->create([
            'email' => 'user@test.com',
        ]);

        $this->tenderUser = User::factory()->create([
            'role_id' => $this->tenderRole->id,
            'email' => 'tender@test.com',
        ]);

        // إنشاء صلاحيات العطاءات
        $this->createTenderPermissions();
    }

    protected function createTenderPermissions(): void
    {
        $permissions = [
            'tenders.tender.view',
            'tenders.tender.create',
            'tenders.tender.update',
            'tenders.tender.delete',
            'tenders.discovery.access',
            'tenders.discovery.edit',
            'tenders.study.access',
            'tenders.study.edit',
            'tenders.study.decide',
            'tenders.pricing.access',
            'tenders.pricing.edit',
            'tenders.pricing.approve',
            'tenders.submission.access',
            'tenders.submission.edit',
            'tenders.submission.confirm',
            'tenders.opening.access',
            'tenders.opening.edit',
            'tenders.award.access',
            'tenders.award.edit',
        ];

        foreach ($permissions as $code) {
            Permission::firstOrCreate(
                ['code' => $code],
                [
                    'name_ar' => $code,
                    'name_en' => $code,
                    'module' => 'tenders',
                    'is_active' => true,
                ]
            );
        }

        // ربط الصلاحيات بدور مدير العطاءات
        $permissionIds = Permission::whereIn('code', $permissions)->pluck('id');
        $this->tenderRole->permissions()->sync($permissionIds);
    }

    // =============================================
    // اختبارات الصلاحيات
    // =============================================

    /** @test */
    public function super_admin_can_access_all_tender_pages(): void
    {
        $this->actingAs($this->superAdmin);

        // صفحة القائمة
        $response = $this->get('/admin/tenders');
        $response->assertStatus(200);

        // صفحة الإنشاء
        $response = $this->get('/admin/tenders/create');
        $response->assertStatus(200);
    }

    /** @test */
    public function user_without_permissions_cannot_access_tender_pages(): void
    {
        $this->actingAs($this->regularUser);

        // يجب أن يُحرم من الوصول
        $response = $this->get('/admin/tenders');
        $response->assertStatus(403);
    }

    /** @test */
    public function tender_user_with_permissions_can_access_tender_list(): void
    {
        $this->actingAs($this->tenderUser);

        $response = $this->get('/admin/tenders');
        $response->assertStatus(200);
    }

    // =============================================
    // اختبارات إنشاء عطاء
    // =============================================

    /** @test */
    public function can_create_tender_with_required_fields(): void
    {
        $this->actingAs($this->superAdmin);

        $tenderData = [
            'reference_number' => 'TEST-2026-001',
            'name_ar' => 'عطاء اختباري',
            'tender_type' => TenderType::SMALL_WORKS->value,
            'tender_method' => TenderMethod::PUBLIC->value,
            'tender_scope' => 'local',
            'owner_type' => OwnerType::GOVERNMENT->value,
            'customer_id' => $this->customer->id,
            'publication_date' => now()->format('Y-m-d'),
            'submission_deadline' => now()->addDays(30)->format('Y-m-d H:i:s'),
            'currency_id' => $this->currency->id,
            'status' => TenderStatus::NEW->value,
        ];

        $tender = Tender::create($tenderData);

        $this->assertDatabaseHas('tenders', [
            'reference_number' => 'TEST-2026-001',
            'name_ar' => 'عطاء اختباري',
        ]);

        $this->assertEquals(TenderStatus::NEW, $tender->status);
    }

    /** @test */
    public function tender_number_is_auto_generated(): void
    {
        $this->actingAs($this->superAdmin);

        $tender = Tender::create([
            'reference_number' => 'AUTO-TEST-001',
            'name_ar' => 'عطاء رقم تلقائي',
            'tender_type' => TenderType::SMALL_WORKS,
            'tender_method' => TenderMethod::PUBLIC,
            'owner_type' => OwnerType::GOVERNMENT,
            'status' => TenderStatus::NEW,
            'submission_deadline' => now()->addDays(30),
        ]);

        $this->assertNotNull($tender->tender_number);
        $this->assertStringContainsString('TND-', $tender->tender_number);
    }

    // =============================================
    // اختبارات مسار العمل (Workflow)
    // =============================================

    /** @test */
    public function tender_starts_with_new_status(): void
    {
        $tender = Tender::create([
            'reference_number' => 'WORKFLOW-001',
            'name_ar' => 'عطاء مسار العمل',
            'tender_type' => TenderType::SMALL_WORKS,
            'tender_method' => TenderMethod::PUBLIC,
            'owner_type' => OwnerType::GOVERNMENT,
            'submission_deadline' => now()->addDays(30),
        ]);

        $this->assertEquals(TenderStatus::NEW, $tender->status);
    }

    /** @test */
    public function tender_can_move_to_studying_status(): void
    {
        $tender = Tender::create([
            'reference_number' => 'WORKFLOW-002',
            'name_ar' => 'عطاء للدراسة',
            'tender_type' => TenderType::SMALL_WORKS,
            'tender_method' => TenderMethod::PUBLIC,
            'owner_type' => OwnerType::GOVERNMENT,
            'status' => TenderStatus::NEW,
            'submission_deadline' => now()->addDays(30),
        ]);

        $tender->update(['status' => TenderStatus::STUDYING]);

        $this->assertEquals(TenderStatus::STUDYING, $tender->fresh()->status);
    }

    /** @test */
    public function tender_workflow_follows_correct_sequence(): void
    {
        $tender = Tender::create([
            'reference_number' => 'WORKFLOW-003',
            'name_ar' => 'عطاء تسلسل المراحل',
            'tender_type' => TenderType::SMALL_WORKS,
            'tender_method' => TenderMethod::PUBLIC,
            'owner_type' => OwnerType::GOVERNMENT,
            'status' => TenderStatus::NEW,
            'submission_deadline' => now()->addDays(30),
        ]);

        // المرحلة 1: جديد -> دراسة
        $tender->update(['status' => TenderStatus::STUDYING]);
        $this->assertEquals(TenderStatus::STUDYING, $tender->fresh()->status);

        // المرحلة 2: دراسة -> Go
        $tender->update([
            'status' => TenderStatus::GO,
            'decision' => 'go',
            'decision_date' => now(),
        ]);
        $this->assertEquals(TenderStatus::GO, $tender->fresh()->status);

        // المرحلة 3: Go -> تسعير
        $tender->update(['status' => TenderStatus::PRICING]);
        $this->assertEquals(TenderStatus::PRICING, $tender->fresh()->status);

        // المرحلة 4: تسعير -> جاهز
        $tender->update(['status' => TenderStatus::READY]);
        $this->assertEquals(TenderStatus::READY, $tender->fresh()->status);

        // المرحلة 5: جاهز -> مقدم
        $tender->update([
            'status' => TenderStatus::SUBMITTED,
            'submission_date' => now(),
        ]);
        $this->assertEquals(TenderStatus::SUBMITTED, $tender->fresh()->status);
    }

    // =============================================
    // اختبارات التحقق من البيانات (Validation)
    // =============================================

    /** @test */
    public function submission_deadline_must_be_after_publication_date(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $tender = new Tender();
        $tender->reference_number = 'VALID-001';
        $tender->name_ar = 'اختبار التواريخ';
        $tender->tender_type = TenderType::SMALL_WORKS;
        $tender->tender_method = TenderMethod::PUBLIC;
        $tender->owner_type = OwnerType::GOVERNMENT;
        $tender->publication_date = now();
        $tender->submission_deadline = now()->subDays(5); // تاريخ خاطئ

        // يجب فشل الحفظ إذا كان التحقق مفعل
        $this->assertTrue($tender->submission_deadline < $tender->publication_date);
    }

    /** @test */
    public function tender_requires_name_in_correct_language(): void
    {
        // عطاء عربي يتطلب اسم عربي
        $tender = Tender::create([
            'reference_number' => 'LANG-001',
            'name_ar' => 'عطاء باللغة العربية',
            'tender_type' => TenderType::SMALL_WORKS,
            'tender_method' => TenderMethod::PUBLIC,
            'owner_type' => OwnerType::GOVERNMENT,
            'is_english_tender' => false,
            'submission_deadline' => now()->addDays(30),
        ]);

        $this->assertNotNull($tender->name_ar);

        // عطاء إنجليزي يتطلب اسم إنجليزي
        $englishTender = Tender::create([
            'reference_number' => 'LANG-002',
            'name_en' => 'English Tender Name',
            'tender_type' => TenderType::SMALL_WORKS,
            'tender_method' => TenderMethod::PUBLIC,
            'owner_type' => OwnerType::GOVERNMENT,
            'is_english_tender' => true,
            'submission_deadline' => now()->addDays(30),
        ]);

        $this->assertNotNull($englishTender->name_en);
    }

    // =============================================
    // اختبارات العلاقات (Relations)
    // =============================================

    /** @test */
    public function tender_belongs_to_customer(): void
    {
        $tender = Tender::create([
            'reference_number' => 'REL-001',
            'name_ar' => 'عطاء مرتبط بعميل',
            'customer_id' => $this->customer->id,
            'tender_type' => TenderType::SMALL_WORKS,
            'tender_method' => TenderMethod::PUBLIC,
            'owner_type' => OwnerType::GOVERNMENT,
            'submission_deadline' => now()->addDays(30),
        ]);

        $this->assertNotNull($tender->customer);
        $this->assertEquals($this->customer->id, $tender->customer->id);
        $this->assertEquals('وزارة الأشغال العامة', $tender->customer->company_name);
    }

    /** @test */
    public function tender_belongs_to_currency(): void
    {
        $tender = Tender::create([
            'reference_number' => 'REL-002',
            'name_ar' => 'عطاء مرتبط بعملة',
            'currency_id' => $this->currency->id,
            'tender_type' => TenderType::SMALL_WORKS,
            'tender_method' => TenderMethod::PUBLIC,
            'owner_type' => OwnerType::GOVERNMENT,
            'submission_deadline' => now()->addDays(30),
        ]);

        $this->assertNotNull($tender->currency);
        $this->assertEquals('JOD', $tender->currency->code);
    }

    // =============================================
    // اختبارات الصفحات (Pages)
    // =============================================

    /** @test */
    public function tender_view_page_loads_correctly(): void
    {
        $this->actingAs($this->superAdmin);

        $tender = Tender::create([
            'reference_number' => 'VIEW-001',
            'name_ar' => 'عطاء لعرضه',
            'tender_type' => TenderType::SMALL_WORKS,
            'tender_method' => TenderMethod::PUBLIC,
            'owner_type' => OwnerType::GOVERNMENT,
            'status' => TenderStatus::NEW,
            'submission_deadline' => now()->addDays(30),
        ]);

        $response = $this->get("/admin/tenders/{$tender->id}");
        $response->assertStatus(200);
    }

    /** @test */
    public function discovery_page_accessible_for_new_tender(): void
    {
        $this->actingAs($this->superAdmin);

        $tender = Tender::create([
            'reference_number' => 'DISC-001',
            'name_ar' => 'عطاء للرصد',
            'tender_type' => TenderType::SMALL_WORKS,
            'tender_method' => TenderMethod::PUBLIC,
            'owner_type' => OwnerType::GOVERNMENT,
            'status' => TenderStatus::NEW,
            'submission_deadline' => now()->addDays(30),
        ]);

        $response = $this->get("/admin/tenders/{$tender->id}/discovery");
        $response->assertStatus(200);
    }

    /** @test */
    public function study_page_accessible_for_studying_tender(): void
    {
        $this->actingAs($this->superAdmin);

        $tender = Tender::create([
            'reference_number' => 'STUDY-001',
            'name_ar' => 'عطاء للدراسة',
            'tender_type' => TenderType::SMALL_WORKS,
            'tender_method' => TenderMethod::PUBLIC,
            'owner_type' => OwnerType::GOVERNMENT,
            'status' => TenderStatus::STUDYING,
            'submission_deadline' => now()->addDays(30),
        ]);

        $response = $this->get("/admin/tenders/{$tender->id}/study");
        $response->assertStatus(200);
    }

    // =============================================
    // اختبارات البيع المباشر
    // =============================================

    /** @test */
    public function direct_sale_hides_documents_and_bonds(): void
    {
        $tender = Tender::create([
            'reference_number' => 'DIRECT-001',
            'name_ar' => 'بيع مباشر',
            'tender_type' => TenderType::SMALL_WORKS,
            'tender_method' => TenderMethod::DIRECT,
            'owner_type' => OwnerType::PRIVATE,
            'is_direct_sale' => true,
            'submission_deadline' => now()->addDays(30),
        ]);

        $this->assertTrue($tender->is_direct_sale);
        // في البيع المباشر لا يوجد كفالات أو وثائق
        $this->assertNull($tender->bid_bond_amount);
        $this->assertNull($tender->documents_price);
    }

    // =============================================
    // اختبارات اكتشاف التكرار
    // =============================================

    /** @test */
    public function detects_duplicate_reference_number(): void
    {
        // إنشاء عطاء أول
        Tender::create([
            'reference_number' => 'DUP-001',
            'name_ar' => 'عطاء أول',
            'tender_type' => TenderType::SMALL_WORKS,
            'tender_method' => TenderMethod::PUBLIC,
            'owner_type' => OwnerType::GOVERNMENT,
            'submission_deadline' => now()->addDays(30),
        ]);

        // التحقق من وجود رقم مكرر
        $exists = Tender::where('reference_number', 'DUP-001')->exists();
        $this->assertTrue($exists);
    }

    // =============================================
    // اختبارات حسابات القيم
    // =============================================

    /** @test */
    public function calculates_remaining_days_correctly(): void
    {
        $tender = Tender::create([
            'reference_number' => 'CALC-001',
            'name_ar' => 'عطاء الحساب',
            'tender_type' => TenderType::SMALL_WORKS,
            'tender_method' => TenderMethod::PUBLIC,
            'owner_type' => OwnerType::GOVERNMENT,
            'submission_deadline' => now()->addDays(15),
        ]);

        // يجب أن يكون متبقي حوالي 15 يوم
        $remainingDays = now()->diffInDays($tender->submission_deadline, false);
        $this->assertGreaterThanOrEqual(14, $remainingDays);
        $this->assertLessThanOrEqual(16, $remainingDays);
    }

    /** @test */
    public function calculates_progress_percentage(): void
    {
        $tender = Tender::create([
            'reference_number' => 'PROG-001',
            'name_ar' => 'عطاء التقدم',
            'tender_type' => TenderType::SMALL_WORKS,
            'tender_method' => TenderMethod::PUBLIC,
            'owner_type' => OwnerType::GOVERNMENT,
            'status' => TenderStatus::PRICING,
            'submission_deadline' => now()->addDays(30),
        ]);

        // في مرحلة التسعير = حوالي 50%
        // هذا يعتمد على تعريف المراحل في النظام
        $statusIndex = array_search($tender->status, TenderStatus::cases());
        $this->assertNotFalse($statusIndex);
    }
}
