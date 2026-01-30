# تغطية وثائق العطاءات الأردنية المعيارية - 100%

## ملخص التحديثات

تم تحديث نظام العطاءات ليتوافق بالكامل مع **وثائق العطاءات الأردنية المعيارية** الصادرة عن دائرة لوازم حكومية.

---

## 1. تحديث TenderType Enum

### الملف: `app/Enums/TenderType.php`

تم إضافة 12 نوع عطاء:

| النوع | الوصف | الحد الأقصى/الأدنى |
|-------|-------|-------------------|
| `SMALL_WORKS` | عطاء أشغال صغيرة | حتى 500,000 د.أ |
| `LARGE_WORKS` | عطاء أشغال كبيرة | أكثر من 500,000 د.أ |
| `PURCHASE_REQUEST` | طلب شراء | حسب الجهة |
| `SUPPLIES_REQUEST` | طلب لوازم | حسب الجهة |
| `INDIVIDUAL_SERVICES` | خدمات أفراد | - |
| `SUBCONTRACTING` | تعاقد فرعي | 33% كحد أقصى |
| `CONSTRUCTION` | أشغال إنشائية | - |
| `SUPPLY` | توريدات | - |
| `SERVICES` | خدمات | - |
| `DESIGN_BUILD` | تصميم وتنفيذ | - |
| `FRAMEWORK` | اتفاقية إطارية | - |
| `TURNKEY` | تسليم مفتاح | - |

### الدوال المساعدة المضافة:
- `requiresBidBond()` - هل يتطلب ضمان عطاء
- `requiresDocumentPurchase()` - هل يتطلب شراء وثائق
- `getMaxEstimatedValue()` - الحد الأقصى للقيمة التقديرية
- `getMinEstimatedValue()` - الحد الأدنى للقيمة التقديرية
- `isGovernment()` - هل هو عطاء حكومي

---

## 2. Migration جديد

### الملف: `database/migrations/2026_01_30_000001_add_jordanian_tender_enhancements.php`

### الحقول الجديدة في جدول `tenders`:

#### التصنيف والتخصص:
- `classification_field` - حقل التصنيف (مباني، طرق، مياه، إلخ)
- `classification_specialty` - التخصص
- `classification_category` - الفئة (أولى، ثانية، إلخ)
- `classification_scope` - النطاق المالي

#### فترة الاعتراض:
- `objection_period_days` - مدة فترة الاعتراض (افتراضي: 7 أيام)
- `objection_period_start` - بداية فترة الاعتراض
- `objection_period_end` - نهاية فترة الاعتراض
- `objection_fee` - رسم الاعتراض (افتراضي: 500 د.أ)

#### اجتماع ما قبل تقديم العطاءات:
- `pre_bid_meeting_required` - هل الاجتماع مطلوب
- `pre_bid_meeting_date` - موعد الاجتماع
- `pre_bid_meeting_location` - مكان الاجتماع
- `pre_bid_meeting_minutes` - محضر الاجتماع

#### الأفضليات السعرية:
- `allows_price_preferences` - تسمح بالأفضليات
- `sme_preference_percentage` - نسبة أفضلية SME
- `local_products_preference` - أفضلية للمنتجات المحلية

#### المقاولين الفرعيين:
- `allows_subcontracting` - يسمح بالتعاقد الفرعي
- `max_subcontracting_percentage` - الحد الأقصى (33%)
- `local_subcontractor_percentage` - الحد الأدنى للمحليين (10%)

#### الائتلافات:
- `allows_consortium` - يسمح بالائتلافات
- `max_consortium_members` - الحد الأقصى لأعضاء الائتلاف

#### الإقرارات المطلوبة:
- `esmp_required` - خطة الإدارة البيئية والاجتماعية
- `code_of_conduct_required` - قواعد السلوك
- `anti_corruption_declaration_required` - إقرار مكافحة الفساد
- `conflict_of_interest_declaration_required` - إقرار عدم تضارب المصالح

#### معايير التقييم:
- `technical_pass_score` - درجة النجاح الفني (افتراضي: 70%)
- `financial_weight` - وزن التقييم المالي
- `technical_weight` - وزن التقييم الفني

#### التصحيحات الحسابية:
- `allow_arithmetic_corrections` - السماح بالتصحيحات
- `words_over_numbers_precedence` - أولوية الكلمات على الأرقام

### الجداول الجديدة:

| الجدول | الوصف |
|--------|-------|
| `tender_consortiums` | الائتلافات |
| `tender_consortium_members` | أعضاء الائتلافات |
| `tender_declarations` | الإقرارات والتعهدات |
| `tender_objections` | الاعتراضات |
| `tender_price_preferences` | الأفضليات السعرية |
| `tender_proposed_subcontractors` | المقاولين الفرعيين المقترحين |
| `tender_arithmetic_corrections` | التصحيحات الحسابية |

---

## 3. النماذج الجديدة (Models)

### 3.1 TenderConsortium
**الملف:** `app/Models/Tenders/TenderConsortium.php`

يدير الائتلافات بين الشركات:
- نوع الاتفاقية (كاملة / خطاب نوايا)
- الشريك الرئيسي وحصته
- توزيع نطاق العمل
- التوثيق الرسمي

### 3.2 TenderConsortiumMember
**الملف:** `app/Models/Tenders/TenderConsortiumMember.php`

يدير أعضاء الائتلاف:
- نسبة الحصة لكل عضو
- تحديد الشريك الرئيسي
- التحقق من أن مجموع الحصص = 100%

### 3.3 TenderDeclaration
**الملف:** `app/Models/Tenders/TenderDeclaration.php`

يدير 8 أنواع من الإقرارات المطلوبة:

| النوع | الوصف |
|-------|-------|
| `esmp_commitment` | الالتزام بخطة الإدارة البيئية والاجتماعية |
| `code_of_conduct` | قواعد السلوك |
| `other_payments` | إقرار الدفعات الأخرى |
| `prohibited_payments` | إقرار المدفوعات المحظورة |
| `no_conflict_of_interest` | إقرار عدم تضارب المصالح |
| `anti_corruption` | إقرار مكافحة الفساد |
| `eligibility` | إقرار الأهلية |
| `validity_acceptance` | قبول فترة الصلاحية |

### 3.4 TenderObjection
**الملف:** `app/Models/Tenders/TenderObjection.php`

يدير workflow الاعتراضات:
- 7 أيام للرد على الاعتراض
- التصعيد للجنة الشكاوى (500 د.أ رسوم)
- تتبع قرارات اللجنة

### 3.5 TenderPricePreference
**الملف:** `app/Models/Tenders/TenderPricePreference.php`

يدير 6 أنواع من الأفضليات السعرية:

| النوع | النسبة |
|-------|-------|
| `sme` | 5% للمشاريع الصغيرة والمتوسطة |
| `women_ownership` | 2% ملكية نساء |
| `youth_ownership` | 2% ملكية شباب |
| `women_management` | 2% إدارة نساء |
| `youth_management` | 2% إدارة شباب |
| `disability` | 1% توظيف ذوي إعاقة |

### 3.6 TenderProposedSubcontractor
**الملف:** `app/Models/Tenders/TenderProposedSubcontractor.php`

يدير المقاولين الفرعيين المقترحين:
- الحد الأقصى 33% من قيمة العقد
- الحد الأدنى 10% من المحافظة المحلية
- تتبع الموافقات والرفض

### 3.7 TenderArithmeticCorrection
**الملف:** `app/Models/Tenders/TenderArithmeticCorrection.php`

يدير 10 أنواع من التصحيحات الحسابية:
- تعارض سعر الوحدة والإجمالي
- خطأ في جمع المجاميع الفرعية
- تعارض الكلمات والأرقام
- سعر وحدة مفقود
- حساب الخصم
- بند غير مسعر
- سعر غير واضح
- أسعار مرتفعة في البداية (Front Loading)
- سعر منخفض بشكل غير طبيعي
- تصحيح آخر

---

## 4. تحديث Tender Model

### الملف: `app/Models/Tender.php`

تمت إضافة:
- جميع الحقول الجديدة في `$fillable`
- جميع التحويلات في `$casts`
- العلاقات الجديدة:
  - `consortiums()` - الائتلافات
  - `declarations()` - الإقرارات
  - `objections()` - الاعتراضات
  - `pricePreferences()` - الأفضليات السعرية
  - `proposedSubcontractors()` - المقاولين الفرعيين
  - `arithmeticCorrections()` - التصحيحات الحسابية

---

## 5. تحديث TenderResource

### الملف: `app/Filament/Resources/TenderResource.php`

تمت إضافة:

### تبويب جديد: "المتطلبات الأردنية"
يحتوي على:
- قسم التصنيف والتخصص المطلوب
- قسم فترة الاعتراض
- قسم اجتماع ما قبل تقديم العطاءات
- قسم الأفضليات السعرية
- قسم المقاولين الفرعيين
- قسم الائتلافات
- قسم الإقرارات المطلوبة
- قسم معايير التقييم
- قسم التصحيحات الحسابية

### RelationManagers جديدة:
- `ObjectionsRelationManager` - إدارة الاعتراضات
- `DeclarationsRelationManager` - إدارة الإقرارات
- `ConsortiumsRelationManager` - إدارة الائتلافات
- `PricePreferencesRelationManager` - إدارة الأفضليات السعرية
- `ArithmeticCorrectionsRelationManager` - إدارة التصحيحات الحسابية

---

## 6. ملخص الفجوات المغلقة

| الفجوة | الحالة |
|--------|-------|
| أنواع العطاءات حسب القيمة | ✅ مغلقة |
| فترة الاعتراض (التوقف) | ✅ مغلقة |
| التصعيد للجنة الشكاوى | ✅ مغلقة |
| الأفضليات السعرية للـ SME | ✅ مغلقة |
| الأفضليات للنساء والشباب | ✅ مغلقة |
| إدارة الائتلافات | ✅ مغلقة |
| الإقرارات والتعهدات (8 أنواع) | ✅ مغلقة |
| التصحيحات الحسابية | ✅ مغلقة |
| نسب المقاولين الفرعيين | ✅ مغلقة |
| معايير التقييم الفني/المالي | ✅ مغلقة |

---

## تاريخ التحديث
- **التاريخ:** 2026-01-30
- **الإصدار:** 2.0.0
- **المرجع:** وثائق العطاءات الأردنية المعيارية - دائرة لوازم حكومية
