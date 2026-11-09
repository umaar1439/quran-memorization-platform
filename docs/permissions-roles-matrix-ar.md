# قائمة الصلاحيات والأدوار الكاملة — دليل التحكم التام

> مرجع مُستخرج من الكود نفسه (Seeder + Policies + Gates + المسارات + FormRequests) بتاريخ 2026-11-09.
> عند أي تعارض، الكود هو المصدر النهائي. الملفات المرجعية:
> `database/seeders/RoleAndPermissionSeeder.php`، `app/Policies/*`، `app/Providers/AuthServiceProvider.php`،
> `app/Http/Requests/*`، `routes/web.php`، `app/Http/Middleware/ResolveTenant.php`، `app/Queries/UserDirectoryQuery.php`.

---

## 1) كيف يعمل نظام الصلاحيات في هذا المشروع

| العنصر | التفصيل |
|---|---|
| المكتبة | `spatie/laravel-permission` v6.25 |
| نمط الفرق (Teams) | مُفعّل: `teams => true` و`team_foreign_key => organization_id` |
| الصلاحيات | **28 صلاحية عالمية** (بلا منظمة) تُنشأ مرة واحدة في `RoleAndPermissionSeeder::run()` |
| الأدوار | **6 أدوار** تُنشأ **لكل منظمة** عبر `createRolesForOrganization($organizationId)` — أي أن لكل منظمة نسخة مستقلة من الأدوار، ويمكن أن تختلف صلاحيات «المشرف» بين منظمة وأخرى |
| سياق الفريق | `ResolveTenant` ينفّذ `setPermissionsTeamId($organization_id)` في كل طلب، وبعده يعمل `hasRole()` و`can()` داخل نطاق المنظمة |
| الأدوار القابلة للإسناد من الواجهة | `UserDirectoryQuery::ROLES = [org-admin, supervisor, teacher, student, parent]` — أي **`super-admin` لا يُسند من الواجهة** (يتطلب Seeder/tinker) |
| عدد أدوار المستخدم | الواجهة تُسند **دوراً واحداً** لكل مستخدم (`assignRole` / `syncRoles`). المكتبة تدعم أكثر من دور لكن الشاشة الحالية لا |
| طبقات الحماية الأربع | ① Route middleware (`role:` أو `can:`) → ② `FormRequest::authorize()` → ③ `$this->authorize()` في Controller → ④ Policy (تحقق من المستأجر + الملكية) |
| ما يراه الواجهة | `HandleInertiaRequests` يشارك `auth.roles` و`auth.permissions` — تُستخدم **لإخفاء الأزرار فقط**، والخادم يفرض الصلاحية دائماً |
| استثناء super-admin | كل Policy تتجاوز فحص `organization_id` لصالحه، لكنه **لا يتجاوز** فحص الصلاحية (`can`) ولا حصر الأدوار في المسارات |

### القواعد السياقية (Scope) المطبّقة فوق الصلاحية

1. **حد المستأجر**: كل Policy تقارن `user->organization_id` بـ `record->organization_id` (إلا `super-admin`).
2. **حد الملكية**: المعلم يرى/يعدّل سجلاته فقط (`teacher_id === user->id`)، والطالب ملفه فقط.
3. **حد الولي**: `guardian_student.permissions` (JSONB) بمفاتيح `view_profile` / `view_recitations` / `view_exam_results`.
4. **حد الحالة**: لا تعديل على امتحان/نتيجة بعد الاعتماد (`ExamStatus::Approved`, `ExamResultStatus::Approved`).
5. **حد الميزة**: الشهادات تتطلب `certificates.enabled` مفعّلاً للمنظمة (`FeatureFlag`).

---

## 2) جدول الصلاحيات الكامل (28 صلاحية)

| # | الصلاحية | الوحدة | ماذا تسمح | تُفحص في (الكود) | الأدوار التي تملكها الآن |
|---|---|---|---|---|---|
| 1 | `users.view` | المستخدمون | عرض قائمة المستخدمين وملفاتهم | `UserPolicy::viewAny/view` | org-admin, super-admin |
| 2 | `users.create` | المستخدمون | إنشاء مستخدم | `UserPolicy::create` + `StoreUserRequest::authorize` | org-admin, super-admin |
| 3 | `users.update` | المستخدمون | تعديل مستخدم | `UserPolicy::update` | org-admin, super-admin |
| 4 | `users.delete` | المستخدمون | حذف مستخدم (لا يمكن حذف النفس) | `UserPolicy::delete` + `UserController::destroy` | org-admin, super-admin |
| 5 | `teachers.view` | المعلمون | عرض قائمة المعلمين وملفاتهم | `TeacherProfilePolicy::viewAny/view` | org-admin, supervisor, super-admin |
| 6 | `teachers.create` | المعلمون | إضافة معلم | `TeacherProfilePolicy::create` + `StoreTeacherRequest` | org-admin, supervisor, super-admin |
| 7 | `teachers.update` | المعلمون | تعديل معلم | `TeacherProfilePolicy::update` | org-admin, supervisor, super-admin |
| 8 | `teachers.delete` | المعلمون | حذف معلم | `TeacherProfilePolicy::delete` | org-admin, supervisor, super-admin |
| 9 | `circles.view` | الحلقات | عرض الحلقات (المعلم: حلقاته فقط) | `CirclePolicy::viewAny/view` + `can:circles.view` | org-admin, supervisor, teacher, super-admin |
| 10 | `circles.manage` | الحلقات | إنشاء/تعديل/حذف حلقة | `CirclePolicy::create/update/delete` + `can:circles.manage` | org-admin, supervisor, super-admin |
| 11 | `students.view` | الطلاب | عرض قائمة الطلاب وملفاتهم وأهدافهم | `StudentProfilePolicy::viewAny` + `StudentGoalPolicy::view` + `MosquePolicy::viewAny/view` | org-admin, supervisor, teacher, super-admin |
| 12 | `students.manage` | الطلاب | إضافة/تعديل/حذف طالب + إنشاء هدف + إنشاء مسجد | `StudentProfilePolicy::create/update/delete` + `StudentGoalPolicy::create` + `MosquePolicy::create` | org-admin, supervisor, super-admin |
| 13 | `students.transfer` | الطلاب | عرض طلبات نقل الطلاب | `StudentTransferPolicy::viewAny/view` | org-admin, supervisor, super-admin |
| 14 | `recitations.create` | التسميع | تسجيل تسميع جديد | `RecitationPolicy::create` + `StoreRecitationRequest` | org-admin, teacher, super-admin ⚠️ |
| 15 | `recitations.view` | التسميع | عرض التسميعات | `RecitationPolicy::viewAny/view` | org-admin, supervisor, teacher, student, parent, super-admin |
| 16 | `recitations.update` | التسميع | تعديل تسميع | `RecitationPolicy::update` | org-admin, teacher, super-admin |
| 17 | `attendance.view` | الحضور | عرض حضور المعلمين | `TeacherAttendancePolicy::viewAny/view` | org-admin, supervisor, teacher, super-admin |
| 18 | `reports.generate` | التقارير | توليد تقرير (POST) | `ReportPolicy::generate` + `StoreReportRequest` | org-admin, supervisor, super-admin |
| 19 | `reports.download` | التقارير | عرض/تنزيل تقرير | `ReportPolicy::viewAny/view/download` | org-admin, supervisor, super-admin |
| 20 | `certificates.generate` | الشهادات | توليد شهادة | `CertificatePolicy::generate/viewAny` + `StoreCertificateRequest` | org-admin, super-admin |
| 21 | `settings.manage` | الإعدادات | تعديل إعدادات المنظمة (ومصطلحاتها) | `SettingPolicy::manage` + `SettingsController` | org-admin, super-admin |
| 22 | `terminology.manage` | المصطلحات | تعديل المصطلحات الديناميكية | ❗ **غير مستخدمة في أي كود حالياً** (المصطلحات تُعدّل عبر `settings.manage`) | org-admin, super-admin |
| 23 | `features.manage` | أعلام الميزات | إدارة Feature Flags للمنظمة | `FeatureFlagPolicy::viewAny/view/manage` | org-admin, super-admin |
| 24 | `exams.view` | الامتحانات | عرض الامتحانات والنتائج | `ExamPolicy::viewAny/view` + `ExamResultPolicy::viewAny/view` | org-admin, supervisor, teacher, super-admin |
| 25 | `exams.create` | الامتحانات | إنشاء/تعديل امتحان وإدخال نتيجة | `ExamPolicy::create/update` + `ExamResultPolicy::create/update` | org-admin, supervisor, super-admin |
| 26 | `exams.approve` | الامتحانات | اعتماد/تصحيح نتيجة امتحان | `ExamResultPolicy::approve` + `CorrectExamResultAction` (و`ExamPolicy::approve` معرّفة لكن لا مسار يستخدمها) | org-admin, super-admin |
| 27 | `roles.view` | الأدوار والصلاحيات | عرض مصفوفة صلاحيات الأدوار | `RolePolicy::viewAny/view` + `can:roles.view` | org-admin, super-admin |
| 28 | `roles.manage` | الأدوار والصلاحيات | تعديل صلاحيات أي دور (عدا `super-admin`) | `RolePolicy::update` + `SyncRolePermissionsRequest` + `can:roles.manage` | org-admin, super-admin |
| 29 | `attendance.manage` | حضور الطلاب | تسجيل/تصحيح حضور الطلاب اليومي | `AttendancePolicy::create` + `StoreAttendanceRequest`/`BulkAttendanceRequest` + `RecordAttendanceAction`/`BulkRecordAttendanceAction` | org-admin, supervisor, teacher, super-admin |

⚠️ **ملاحظة على #14**: `recitations.create` ممنوحة لـ org-admin وsuper-admin في الـ Seeder، لكن `RecitationPolicy::create()` تشترط `hasRole('teacher')` أيضاً — أي أن المدير يملك الصلاحية ولا يستطيع تسجيل تسميع فعلياً. هذا مثال على «الصلاحية وحدها لا تكفي» (انظر §6).

⚠️ **ملاحظة على #22**: `terminology.manage` صلاحية «ميتة»: موجودة في الجدول وممنوحة للأدوار، لكن لا يوجد أي كود يقرأها. إما أن تُربط بوحدة المصطلحات أو تُحذف لتفادي الالتباس.

---

## 3) الأدوار الستة — النطاق والصلاحيات

| الدور | النطاق | عدد الصلاحيات | الصلاحيات (28) | الصفحة الرئيسية |
|---|---|---|---|---|
| `super-admin` | عابر للمنظمات (يتجاوز `organization_id`) | 28/28 | كل الصلاحيات | `/admin/dashboard` |
| `org-admin` | منظمته فقط | 28/28 | كل الصلاحيات | `/admin/dashboard` |
| `supervisor` | منظمته فقط | 16 | teachers (4) + circles.view + circles.manage + students.view + students.manage + students.transfer + recitations.view + attendance.view + attendance.manage + reports.generate + reports.download + exams.view + exams.create | `/admin/dashboard` |
| `teacher` | حلقاته وسجلاته فقط | 8 | circles.view + students.view + recitations.create + recitations.view + recitations.update + attendance.view + attendance.manage + exams.view | `/teacher/dashboard` |
| `student` | ملفه فقط | 1 | recitations.view | `/student/dashboard` |
| `parent` | أبناؤه المصرّح لهم فقط | 1 | recitations.view | `/parent/dashboard` |

---

## 4) مصفوفة الصلاحيات × الأدوار (الحالة الفعلية اليوم)

| الصلاحية | super-admin | org-admin | supervisor | teacher | student | parent |
|---|:--:|:--:|:--:|:--:|:--:|:--:|
| `users.view` | ✅ | ✅ | — | — | — | — |
| `users.create` | ✅ | ✅ | — | — | — | — |
| `users.update` | ✅ | ✅ | — | — | — | — |
| `users.delete` | ✅ | ✅ | — | — | — | — |
| `teachers.view` | ✅ | ✅ | ✅ | — | — | — |
| `teachers.create` | ✅ | ✅ | ✅ | — | — | — |
| `teachers.update` | ✅ | ✅ | ✅ | — | — | — |
| `teachers.delete` | ✅ | ✅ | ✅ | — | — | — |
| `circles.view` | ✅ | ✅ | ✅ | ✅ | — | — |
| `circles.manage` | ✅ | ✅ | ✅ | — | — | — |
| `students.view` | ✅ | ✅ | ✅ | ✅ | — | — |
| `students.manage` | ✅ | ✅ | ✅ | — | — | — |
| `students.transfer` | ✅ | ✅ | ✅ | — | — | — |
| `recitations.create` | ✅ | ✅ | — | ✅ | — | — |
| `recitations.view` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `recitations.update` | ✅ | ✅ | — | ✅ | — | — |
| `attendance.view` | ✅ | ✅ | ✅ | ✅ | — | — |
| `attendance.manage` | ✅ | ✅ | ✅ | ✅ | — | — |
| `reports.generate` | ✅ | ✅ | ✅ | — | — | — |
| `reports.download` | ✅ | ✅ | ✅ | — | — | — |
| `certificates.generate` | ✅ | ✅ | — | — | — | — |
| `settings.manage` | ✅ | ✅ | — | — | — | — |
| `terminology.manage` | ✅ | ✅ | — | — | — | — |
| `features.manage` | ✅ | ✅ | — | — | — | — |
| `exams.view` | ✅ | ✅ | ✅ | ✅ | — | — |
| `exams.create` | ✅ | ✅ | ✅ | — | — | — |
| `exams.approve` | ✅ | ✅ | — | — | — | — |
| `roles.view` | ✅ | ✅ | — | — | — | — |
| `roles.manage` | ✅ | ✅ | — | — | — | — |
| **الإجمالي** | **28** | **28** | **15** | **7** | **1** | **1** |

---

## 5) الوصول الفعلي لكل جزء من الموقع (middleware + policy)

> العمود الأخير هو الحقيقة النهائية: من يستطيع فعلاً استخدام الجزء، وليس من يملك الصلاحية فقط.

| الجزء | المسار / الصفحة | حارس المسار (middleware) | فحص السياسة (Policy/Gate) | من يصل فعلياً |
|---|---|---|---|---|
| لوحة الإدارة | `GET /admin/dashboard` | `role:org-admin\|supervisor\|super-admin` | Gate `view-admin-dashboard` (أدوار فقط) | org-admin, supervisor, super-admin |
| المساجد | `/admin/mosques` | `role:org-admin\|supervisor\|super-admin` | `circles.view` أو `students.view` للعرض، `students.manage`/`circles.manage` للإنشاء، `circles.manage` للتعديل/الحذف | org-admin, supervisor, super-admin |
| الحلقات (عرض) | `GET /admin/circles` | **`can:circles.view`** (حديثاً) | `CirclePolicy::viewAny/view` — المعلم يرى حلقاته فقط | org-admin, supervisor, teacher, super-admin |
| الحلقات (كتابة) | `POST/PATCH/DELETE /admin/circles` | **`can:circles.manage`** | `CirclePolicy::create/update/delete` | org-admin, supervisor, super-admin |
| الأدوار والصلاحيات | `GET /admin/roles` و`PATCH /admin/roles/{role}` | **`can:roles.view`** / **`can:roles.manage`** | `RolePolicy` (roles.view/roles.manage + نطاق المنظمة + حماية super-admin + منع تصعيد الصلاحيات وقفل النفس) | org-admin, super-admin |
| المستخدمون | `/admin/users` | `role:org-admin\|supervisor\|super-admin` | `users.view/create/update/delete` | org-admin, super-admin (المشرف يمر من middleware ثم يُرفض بالسياسة) |
| المعلمون | `/admin/teachers` | نفس المجموعة | `teachers.*` | org-admin, supervisor, super-admin |
| الطلاب | `/admin/students` | نفس المجموعة | `students.view` للعرض، `students.manage` للكتابة | org-admin, supervisor, super-admin |
| ملف طالب / لوحة طالب | `GET /students/{student}` | `auth` فقط | `StudentProfilePolicy::view` (مدير/مشرف/معلم حلقته/الطالب نفسه/الولي) | حسب السياسة (لا حصر بالأدوار) |
| التسميع | `/recitations` | `auth` فقط | `recitations.view` للعرض، `recitations.create` + دور teacher للتسجيل، `recitations.update` لمنشئ السجل/المدير | teacher (تسجيل)، الجميع تقريباً (عرض مقيّد) |
| الحضور | `/teacher-attendances` | `auth` فقط | `attendance.view` | org-admin, supervisor, teacher (سجلاته) |
| حضور الطلاب (يومي) | `GET /admin/attendance` و`GET /teacher/attendance` — الكتابة: `POST /attendance` و`POST /attendance/bulk` | مجموعة الأدوار الإدارية / `role:teacher` — الكتابة `auth` فقط | `AttendancePolicy::viewAny` للعرض، و`create` للكتابة (`attendance.manage` + نطاق المنظمة + ملكية المعلم للحلقة) | org-admin, supervisor (كل المنظمة) — teacher (حلقاته فقط) |
| أهداف الطلاب | `/student-goals` | `auth` فقط | `students.manage` أو دور teacher/parent/student | org-admin, supervisor, teacher, student, parent |
| نقل الطلاب | `/student-transfers` | `auth` فقط (`page` داخل مجموعة الأدوار) | `students.transfer` للعرض، **`request()`/`approve()` بالأدوار فقط** | عرض: org-admin/supervisor/super-admin — طلب: supervisor/org-admin/super-admin — اعتماد: org-admin/super-admin |
| الامتحانات | `/exams` + `/admin/exams` | `auth` (JSON) و`role:` لصفحة الإدارة | `exams.view` للعرض، `exams.create` للإنشاء/التعديل | JSON: org-admin, supervisor, teacher — صفحة الإدارة: org-admin, supervisor (المعلم يملك `exams.view` لكنه محجوب بالـ role) |
| نتائج الامتحانات | `POST /exam-results*` | `auth` فقط | `exams.create` للإدخال، `exams.approve` للاعتماد/التصحيح | org-admin, supervisor (إدخال) — org-admin/super-admin (اعتماد) |
| الشهادات | `/certificates` | `auth` + `feature:certificates.enabled` | `CertificatePolicy::generate` (دور org-admin/super-admin + صلاحية + علم الميزة) / `viewAny` للطالب والولي | طالب/ولي (عرض شهاداتهم) — org-admin, super-admin (توليد) |
| التحقق من شهادة | `GET /certificates/verify/{code}` | عام + `throttle` | لا يوجد (تحقق علني) | أي زائر |
| التقارير | `/reports` + `/admin/reports` | `auth` / `role:` | `reports.generate` للتوليد، `reports.download` للعرض/التنزيل | org-admin, supervisor (التنزيل للمنشئ أيضاً) |
| الإعدادات | `/admin/settings` | `role:org-admin\|supervisor\|super-admin` | `SettingPolicy::manage` (دور org-admin/super-admin + `settings.manage`) | org-admin, super-admin |
| المصطلحات | نفس الإعدادات | نفس المجموعة | `settings.manage` (`terminology.manage` غير مستخدمة) | org-admin, super-admin |
| أعلام الميزات | `/admin/feature-flags` | نفس المجموعة | `features.manage` + قيد: org-admin يعدّل `certificates.enabled` و`advanced-reports.enabled` فقط، والعالمية لـ super-admin | org-admin (جزئي), super-admin (كامل) |
| سجل التدقيق | `/admin/audit-logs-page` | نفس المجموعة | **بالأدوار فقط** `org-admin`/`super-admin` (لا صلاحية) | org-admin, super-admin |
| صفحات المعلم | `/teacher/*` | `role:teacher` | لا فحص صلاحية في `TeacherCircleController::page` | teacher فقط |
| صفحات الطالب | `/student/*` | `role:student` | `StudentProfilePolicy::view` في لوحة الطالب | student فقط |
| صفحات ولي الأمر | `/parent/*` | `role:parent` | التحقق من وجود ملف ولي (`guardian`) فقط | parent فقط |
| الملف الشخصي | `/profile` | `auth` | لا يوجد (المستخدم يعدّل بياناته) | كل مستخدم مسجّل |

---

## 6) الفجوات التي تمنع «التحكم التام» حالياً

هذه الأماكن تُقرأ فيها **الأدوار مباشرة** (`hasRole`) بدل الصلاحيات، أو يحرسها `role:` middleware، ولذلك **تعديل صلاحيات دور في قاعدة البيانات لا يغيّر سلوكها**:

| # | القدرة | مشفّرة على الدور في | الأثر | الإصلاح المقترح |
|---|---|---|---|---|
| 1 | الدخول إلى `/admin/*` (المساجد، المستخدمون، المعلمون، الطلاب، الإعدادات، الأعلام، التقارير، سجل التدقيق، صفحات الامتحانات والنقل) | `routes/web.php` مجموعة `role:org-admin\|supervisor\|super-admin` | مستخدم يملك كل الصلاحيات لكن دوره ليس إدارياً يُرفض بـ 403 | تحويل كل مجموعة إلى `can:<permission>` كما فُعل في الحلقات |
| 2 | سجل التدقيق | `AuditLogPolicy::viewAny/view` (أدوار فقط) | لا يمكن منح صلاحية «عرض السجلات» لدور آخر، ولا سحبها من org-admin | إضافة `audit_logs.view` واستبدال الدور بالصلاحية |
| 3 | إنشاء/تعديل حضور المعلمين | `TeacherAttendancePolicy::create/update` (أدوار فقط) | `attendance.view` تسمح بالعرض فقط، ولا مسار كتابة لحضور المعلمين أصلاً (الحضور مُشتق من النشاط) | `attendance.manage` أُضيفت لحضور **الطلاب** فقط؛ حضور المعلمين يبقى مُشتقاً فلا حاجة لمسار كتابة |
| 4 | طلب/اعتماد نقل الطالب | `StudentTransferPolicy::request/approve` (أدوار فقط) | `students.transfer` تسمح بالعرض فقط | إضافة `students.transfer.request` و`students.transfer.approve` |
| 5 | لوحة التحكم الإدارية | Gate `view-admin-dashboard` (أدوار فقط) | لا يمكن إنشاء دور «مشرف تقارير» يرى اللوحة | تحويله إلى `dashboard.admin.view` |
| 6 | تسجيل التسميع | `RecitationPolicy::create` يشترط دور `teacher` | org-admin يملك `recitations.create` لكنه لا يستطيع التسجيل | فصل الشرط: صلاحية + نطاق، وإبقاء «ملكية الحلقة» للمعلم |
| 7 | توليد الشهادات والإعدادات والأعلام والتقارير | سياسات تشترط الدور **و**الصلاحية معاً | منح الصلاحية لدور جديد بلا جدوى | إزالة شرط الدور والإبقاء على الصلاحية + نطاق المنظمة |
| 8 | إدارة الأدوار والصلاحيات نفسها | ✅ **منفّذة** عبر `roles.view`/`roles.manage` وشاشة `/admin/roles` | ما زال ناقصاً: إنشاء/حذف أدوار جديدة، وإسناد `super-admin` من الواجهة | شاشة إنشاء الأدوار + إجراء إسناد محمي |
| 9 | `terminology.manage` | غير مستخدمة | صلاحية بلا أثر (التحكم الوهمي) | ربطها بوحدة المصطلحات أو حذفها |
| 10 | إسناد `super-admin` | `UserDirectoryQuery::ROLES` يستثنيه والشاشة تُسند دوراً واحداً | ترقية/تنزيل super-admin تتطلب tinker | إضافة إجراء محمي (super-admin فقط) لإسناد الأدوار |
| 11 | صفحات المعلم/الطالب/الولي | `role:teacher` / `role:student` / `role:parent` | لا يمكن منح وصول لصفحة عبر صلاحية | إن لزم: مجموعات `can:` مقابلة |
| 12 | `roles` تُنشأ لكل منظمة | `createRolesForOrganization` | إضافة دور جديد تتطلب إنشاءه في **كل** منظمة + تصفير الكاش | Action واحد `EnsureRolesForOrganizationAction` + شاشة |

---

## 7) خطة الوصول إلى «التحكم التام» (مقترحة على مراحل)

### المرحلة 1 — توحيد الحراس على الصلاحيات بدل الأدوار
1. في `routes/web.php`: استبدال `role:org-admin|supervisor|super-admin` بمجموعات `can:` لكل وحدة (`can:users.view`، `can:mosques.view`… يحتاج صلاحية عرض موحّدة للمساجد).
2. إضافة صلاحيات جديدة في `RoleAndPermissionSeeder::PERMISSIONS`:
   `mosques.view`، `mosques.manage`، `audit_logs.view`، `attendance.manage`، `students.transfer.request`، `students.transfer.approve`، `dashboard.admin.view`، `roles.view`، `roles.manage`، `roles.assign`.
3. استبدال شروط `hasRole` داخل الـ Policies بالصلاحيات الجديدة (مع الإبقاء على حدود الملكية/المستأجر).
4. تحديث `Gate::define('view-admin-dashboard')` ليقرأ `dashboard.admin.view`.
5. تحديث الاختبارات: `tests/Feature/RoleMiddlewareTest.php` + اختبارات السياسات.
> الأثر: 6–8 ملفات + Seeder + اختبارات. **يحتاج موافقة صريحة قبل التنفيذ** (تغيير معماري في التخويل).

### المرحلة 2 — شاشة «الأدوار والصلاحيات» داخل التطبيق ✅ **منفّذة**
- `GET /admin/roles` (تبويبات الأدوار + مصفوفة الصلاحيات مجمّعة حسب الوحدة) و`PATCH /admin/roles/{role}`.
- الملفات: `RoleController` + `SyncRolePermissionsRequest` + `SyncRolePermissionsAction` + `RolePolicy` + `Pages/Admin/Roles/Index.vue`.
- الصلاحيات: `roles.view` للعرض و`roles.manage` للتعديل (مضافة إلى `RoleAndPermissionSeeder`).
- الحدود المطبّقة: الدور داخل نفس المنظمة فقط، و`super-admin` محمي من التعديل، ومنع منح صلاحية لا يملكها المستخدم، ومنع سحب `roles.manage` من دور يملكه، وكل تعديل يُسجَّل في `audit_logs` (`role.permissions_updated`).
- الاختبارات: `tests/Feature/RolePermissionManagementTest.php` (9 اختبارات).
- ما لم يُنفَّذ بعد: إنشاء/حذف أدوار، وإسناد `super-admin` من الواجهة.

### المرحلة 3 — أدوار متعددة وإسناد محمي
- جعل `StoreUserRequest` يقبل `roles[]` بدل دور واحد، مع منع إسناد `super-admin` إلا لـ super-admin.

### المرحلة 4 — صلاحيات ولي الأمر لكل طفل
- واجهة لإدارة `guardian_student.permissions` (`view_profile` / `view_recitations` / `view_exam_results`) لكل ابن.

---

## 8) كيف تتحكم اليوم فعلياً

| ما تريد | الطريقة الحالية |
|---|---|
| تغيير دور مستخدم | `/admin/users` → «تعديل» → قائمة الدور (5 أدوار) — `UpdateUserAction::syncRoles` |
| إنشاء مستخدم بدور | `/admin/users` → «إضافة» — `CreateUserAction::assignRole` |
| إعطاء `super-admin` | غير متاح من الواجهة؛ يتطلب `tinker` (انظر أدناه) |
| تعديل صلاحيات دور | `/admin/roles` → اختر الدور → عدّل المربعات → «حفظ التغييرات» (أو `tinker` كبديل، انظر أدناه) |
| صلاحية لمستخدم بعينه (فردية) | غير متاحة من الواجهة؛ Spatie تدعمها عبر `model_has_permissions` |

### أوامر tinker جاهزة

```php
php artisan tinker

use App\Models\Organization;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// 0) إلزامي: اضبط سياق المنظمة قبل أي عملية على الأدوار
$org = Organization::query()->where('slug', 'demo-organization')->firstOrFail();
app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

// 1) عرض الأدوار وصلاحياتها
Role::query()->where('organization_id', $org->id)->withCount('permissions')->get(['name']);

// 2) منح صلاحية لدور
Role::findByName('supervisor', 'web')->givePermissionTo('exams.approve');

// 3) سحب صلاحية
Role::findByName('teacher', 'web')->revokePermissionTo('students.view');

// 4) مزامنة كاملة (تحذف ما ليس في القائمة)
Role::findByName('supervisor', 'web')->syncPermissions(['teachers.view', 'circles.view']);

// 5) إسناد دور لمستخدم
$user = User::query()->where('email', 'teacher@example.com')->firstOrFail();
$user->assignRole('supervisor');   // يضيف دوراً ويبقي القديم
$user->syncRoles(['teacher']);     // يستبدل كل الأدوار

// 6) تصفير الكاش بعد أي تغيير مباشر
app(PermissionRegistrar::class)->forgetCachedPermissions();
```

أو من الطرفية مباشرة بعد أي تغيير:

```bash
php artisan permission:cache-reset
```

### ترحيل الصلاحيات الجديدة إلى بيئة قائمة (roles.view / roles.manage)

`RoleAndPermissionSeeder::run()` ينشئ الصلاحيات العالمية فقط، ولا يزامن الأدوار
الموجودة. بعد تحديث الكود على بيئة قائمة، نفّذ مرة واحدة:

```bash
php artisan tinker --execute='$s=new Database\Seeders\RoleAndPermissionSeeder; $s->run(); $r=app(Spatie\Permission\PermissionRegistrar::class); foreach (App\Models\Organization::pluck("id") as $id) { $r->setPermissionsTeamId($id); foreach (["org-admin", "super-admin"] as $name) { $role = Spatie\Permission\Models\Role::where("name", $name)->where("organization_id", $id)->first(); if ($role) { $role->givePermissionTo(["roles.view", "roles.manage"]); } } } $r->forgetCachedPermissions();'
```

المنظمات الجديدة تحصل عليها تلقائياً لأن `createRolesForOrganization()` يزامن
`org-admin`/`super-admin` مع `Permission::all()`.

---

## 9) قواعد أمان يجب الالتزام بها عند التحكم بالصلاحيات

1. **لا تسحب كل صلاحيات `org-admin`** — قد تُقفل نفسك خارج `/admin/users` و`/admin/settings`. أبقِ `super-admin` كطريق إنقاذ.
2. **`super-admin` محمي من التعديل من الواجهة** — الشاشة تمنع تعديله (403) لأنه طريق الإنقاذ إذا سُحبت صلاحية إدارة الأدوار من بقية الأدوار؛ يُضبط من الـ Seeder فقط.
3. **منع رفع الصلاحيات (Privilege Escalation)**: لا يجوز لمن يعدّل الأدوار أن يمنح صلاحية لا يملكها هو نفسه.
4. **حد المستأجر ليس صلاحية**: `withinTenant` يبقى في كل Policy ولا يُستبدل بصلاحية، وإلا انكسر عزل المنظمات.
5. **`role:` middleware ≠ تفويض**: أي مسار محمي بالدور فقط يجب أن يحتوي أيضاً على `authorize()` أو `can:` (هذا سبب خطأ `/admin/circles` سابقاً).
6. **بعد أي تغيير في Seeder**: نفّذ `php artisan test` — الاختبارات تفترض التوزيع الحالي للصلاحيات.
7. **سجّل التغييرات**: كل تعديل على صلاحيات دور يُكتب في `audit_logs` بحدث `role.permissions_updated` مع القيم القديمة والجديدة.
