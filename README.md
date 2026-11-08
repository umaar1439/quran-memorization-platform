# منصة تحفيظ القرآن الكريم

تطبيق Laravel 12 + Inertia.js + Vue 3 لإدارة حلقات تحفيظ القرآن: منظمات، مساجد، حلقات،
معلمون، طلاب، أولياء أمور، تسميع، امتحانات، شهادات، تقارير، حضور، ولوحات تحكم لكل دور.

## المتطلبات

- PHP 8.2+ مع إضافات `pdo`, `mbstring`, `openssl`, `fileinfo`
- Composer 2
- Node.js 20+ و npm
- قاعدة بيانات: PostgreSQL (المُفضل)، أو MySQL/MariaDB، أو SQLite للتطوير السريع
- Redis (اختياري: الكاش والطوابير؛ يعمل النظام بـ `database` بدونه)

## التثبيت

```bash
# 1) اعتماديات PHP و JS
composer install
npm install

# 2) ملف البيئة
cp .env.example .env
php artisan key:generate

# 3) قاعدة البيانات
#    PostgreSQL (القيم الافتراضية في .env.example)
createdb tahfeez && php artisan migrate --seed

#    أو SQLite للتطوير المحلي السريع:
#    touch database/database.sqlite
#    ثم في .env: DB_CONNECTION=sqlite
#    و DB_DATABASE=/absolute/path/to/database/database.sqlite

# 4) بناء الأصول
npm run build
```

## التشغيل

```bash
php artisan serve          # الخادم
npm run dev                # Vite HMR أثناء التطوير
php artisan queue:work     # معالجة الشهادات/التقارير/الإشعارات
php artisan schedule:work  # تشغيل المهام المجدولة محلياً (بديل cron)
```

أو كل ذلك معاً: `composer run dev`.

### المهام المجدولة (الإنتاج)

`routes/console.php` يعرّف المهام التالية بـ `withoutOverlapping()` و`onOneServer()`:

| الأمر | التكرار |
| --- | --- |
| `sanctum:prune-expired --hours=24` | يومياً |
| `queue:prune-failed --hours=168` | أسبوعياً |
| `queue:prune-batches --hours=48` | يومياً |

في الإنتاج أضف cron واحداً:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

## الحسابات التجريبية (`DatabaseSeeder`)

كلمة المرور للجميع: `password`

| الدور | البريد |
| --- | --- |
| مدير النظام العام | `super-admin@example.com` |
| مدير المنظمة | `org-admin@example.com` |
| مشرف | `supervisor@example.com` |
| معلم | `teacher@example.com` |
| طالب | `student@example.com` |
| ولي أمر | `parent@example.com` |

> الـ Seeder تطويري فقط. لا تعتمد عليه في الإنتاج؛ أنشئ أول منظمة وأول `super-admin`
> عبر بيانات بيئة آمنة أو أمر مخصص.

## الاختبارات والجودة

```bash
php artisan test                 # Pest (Feature + Unit)
./vendor/bin/pint                # تنسيق Laravel Pint
./vendor/bin/pint --test         # التحقق دون تعديل
```


## البنية المعمارية

```
Route → Middleware → Controller → FormRequest → Action/Query → Model → Resource
```

| الطبقة | المجلد | الدور |
| --- | --- | --- |
| Controllers | `app/Http/Controllers` | رقيقة: تفويض فقط، بلا استعلامات أو منطق أعمال |
| Requests | `app/Http/Requests` | التحقق + `authorize()` + تحويل إلى DTO |
| Actions | `app/Actions` | حالة استخدام واحدة لكل Action، مع `DB::transaction` |
| Queries | `app/Queries` | استعلامات قراءة قابلة لإعادة الاستخدام (قوائم، لوحات تحكم) |
| DTOs | `app/DTOs` | كائنات `readonly` بين الطبقات بدل المصفوفات الحرة |
| Resources | `app/Http/Resources` | عقد JSON صريح يُخفي الحقول الداخلية (مثل `file_path`) |
| Support | `app/Support` | خدمات صغيرة مشتركة (كاش الأعلام/الإعدادات، تهريب LIKE) |
| Enums | `app/Enums` | قيم مغلقة مع Casts في Eloquent |
| lang | `lang/ar`, `lang/en` | كل نص يظهر للمستخدم (رسائل، تحقق، إشعارات) |

### قواعد سلوكية مهمة

- `BelongsToOrganization` يفرض Global Scope لكل مستأجر ويستثني `super-admin` فقط.
- `User::$fillable` **لا يتضمن** `organization_id` ولا `status` (منع Privilege Escalation)؛
  إسنادهما يتم صراحةً داخل الـ Actions، لا عبر `create()`.
- `FeatureFlagCache` و`SettingsCache` مصدران وحيدان للقراءة والإبطال — لا تُكتب مفاتيح
  الكاش يدوياً داخل Controllers.
- `Logging` و`SecurityHeaders`: HSTS للإنتاج فقط، وCSP يحتوي `unsafe-eval` و`ws:` في
  التطوير فقط (HMR)، ويُشدَّد في الإنتاج.

## الالتزام بقواعد `clinerules`

**مُطبَّق بالكامل**

- `declare(strict_types=1)` في كل ملفات `app/`, `database/`, `routes/`, `tests/`.
- Laravel Pint نظيف على كل الملفات.
- `Model::shouldBeStrict()` + `DB::prohibitDestructiveCommands()` في `AppServiceProvider`.
- لا SQL خام إطلاقاً (لا `DB::raw` ولا `whereRaw` …).
- FormRequest لكل عملية كتابة، و`authorize()` داخل الطلب.
- Actions + DTOs لمسارات المعلم/الطالب، وActions لكل عملية كتابة (تسميع، تصحيح نتيجة،
  نقل طالب، شهادة، تقرير، إعدادات، أعلام ميزات).
- استعلامات القراءة للقوائم ولوحة الحلقة في `app/Queries` (Teacher/Student/Circle + خيارات).
- كل استجابة JSON عبر `JsonResource` مع استبعاد الحقول الداخلية.
- `DB::transaction()` لكل كتابة متعددة الخطوات، و`upsert` للإعدادات.
- توطين كامل لرسائل الخادم (`lang/ar` + `lang/en`) بدل النصوص المضمنة.
- كاش مُبطل عند الكتابة عبر خدمات مركزية، وحدود صريحة لكل قائمة.
- Jobs: `tries` + `backoff` + `timeout` + `failed()` مع تسجيل السياق.
- جدولة بـ `withoutOverlapping()` و`onOneServer()`.

**متبقٍ (دين تقني موثّق، لا كسر وظيفي)**

1. بعض الـ Controllers ما زالت تحتوي استعلامات قراءة بسيطة داخل `page()`
   (Mosques, Users, Circles, Exams, Reports, AuditLogs, Settings, TeacherAttendance).
   النمط جاهز في `app/Queries`؛ النقل تدريجي.
2. `queryClient`/`axios` غير مستخدمة في الواجهة؛ كل القراءات عبر Inertia props.
3. ترجمة نصوص Vue ليست مركزية بعد (لا `vue-i18n`) — نصوص الواجهة عربية مباشرة.
4. لا طبقة API عامة مُصدَّرة (`/api/v1`) — الاستجابات JSON داخلية للواجهة فقط،
   لكنها تتبع عقد Resources موحّداً وجاهزة للنقل إلى `routes/api.php` عند الحاجة.
5. لا CI (GitHub Actions) ولا Larastan/PHPStan بعد.

## الإنتاج

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize          # config + route + event + view caches
php artisan queue:restart
```

تأكد من: `APP_ENV=production`، `APP_DEBUG=false`، `SESSION_SECURE_COOKIE=true`،
`QUEUE_CONNECTION=redis`، ومفتاح `APP_KEY` مُولَّد ولا يُشارَك.

## الرخصة

MIT.
