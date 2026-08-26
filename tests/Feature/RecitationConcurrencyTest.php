<?php

use App\Models\Circle;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\Recitation;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SurahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SurahSeeder::class);
});

/**
 * ينشئ الأدوار والصلاحيات لمنظمة معينة ويعيدها كخريطة role => Role.
 */
function seedRolesForConcurrency(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

/**
 * بيئة عمليات فرعية تحاكي بيئة الاختبار (SQLite + Array Cache + Sync Queue)
 * حتى لا تحاول العمليات الفرعية الاتصال بـ Redis/Postgres من ملف .env.
 */
function concurrencyTestEnv(string $dbFile): array
{
    return [
        'APP_ENV' => 'testing',
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => $dbFile,
        'CACHE_STORE' => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'SESSION_DRIVER' => 'array',
        'MAIL_MAILER' => 'array',
        'BROADCAST_CONNECTION' => 'null',
    ];
}

/**
 * سكربت الإعداد: يعمل في عملية فرعية، يهاجر + يزرع + ينشئ البيانات
 * في ملف SQLite مشترك (FILE وليس :memory:) ليشترك فيه عمليات الـ worker.
 */
function concurrencySetupScript(): string
{
    return <<<'PHP'
<?php

use App\Models\Circle;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SurahSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

[$autoload, $bootstrap, $dbFile] = array_slice($argv, 1);

require $autoload;
$app = require $bootstrap;
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config([
    'database.connections.sqlite.database' => $dbFile,
    'database.connections.sqlite.busy_timeout' => 5000,
    'database.connections.sqlite.journal_mode' => 'WAL',
]);
DB::purge('sqlite');

Artisan::call('migrate:fresh', ['--force' => true]);

(new SurahSeeder)->run();

$org = Organization::factory()->active()->create(['name' => 'Concurrency Org']);

$roleSeeder = new RoleAndPermissionSeeder;
$roleSeeder->run();
$roles = $roleSeeder->createRolesForOrganization($org->id);

$mosque = Mosque::factory()->create(['organization_id' => $org->id]);
$circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

$teacher = User::factory()->create(['organization_id' => $org->id]);
$teacher->assignRole($roles['teacher']);
$circle->update(['teacher_id' => $teacher->id]);

$student = StudentProfile::factory()->create([
    'organization_id' => $org->id,
    'mosque_id' => $mosque->id,
    'circle_id' => $circle->id,
]);

echo json_encode([
    'org_id' => $org->id,
    'teacher_id' => $teacher->id,
    'student_id' => $student->id,
]);
PHP;
}

/**
 * سكربت الـ worker: ينفّذ RecordRecitationAction بنفس idempotency_key
 * ضمن عملية فرعية مستقلة متصلة بنفس ملف SQLite المشترك.
 */
function concurrencyWorkerScript(): string
{
    return <<<'PHP'
<?php

use App\Actions\RecordRecitationAction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

[$autoload, $bootstrap, $dbFile, $teacherId, $studentId, $orgId, $key] = array_slice($argv, 1);

require $autoload;
$app = require $bootstrap;
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config([
    'database.connections.sqlite.database' => $dbFile,
    'database.connections.sqlite.busy_timeout' => 5000,
    'database.connections.sqlite.journal_mode' => 'WAL',
]);
DB::purge('sqlite');

$teacher = User::withoutGlobalScopes()->findOrFail((int) $teacherId);

app(PermissionRegistrar::class)->setPermissionsTeamId((int) $orgId);
Auth::login($teacher);

try {
    $recitation = app(RecordRecitationAction::class)->execute([
        'student_id' => (int) $studentId,
        'type' => 'new',
        'surah_number' => 2,
        'ayah_from' => 1,
        'ayah_to' => 5,
        'idempotency_key' => $key,
    ], $teacher);

    echo 'OK:'.$recitation->id;
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, get_class($e).': '.$e->getMessage());
    exit(1);
}
PHP;
}

test('concurrency: simultaneous identical recitation requests produce exactly one record without unhandled exceptions', function () {
    // ملف SQLite مشترك (FILE وليس :memory:) حتى تتمكن العمليات الفرعية المتوازية من مشاركته.
    $dbFile = tempnam(sys_get_temp_dir(), 'rec_concurrency_');

    $setupPath = tempnam(sys_get_temp_dir(), 'rec_setup_').'.php';
    file_put_contents($setupPath, concurrencySetupScript());

    $setup = new Process(
        [PHP_BINARY, $setupPath, base_path('vendor/autoload.php'), base_path('bootstrap/app.php'), $dbFile],
        base_path(),
        concurrencyTestEnv($dbFile),
    );
    $setup->setTimeout(120);
    $setup->run();

    if ($setup->getExitCode() !== 0) {
        $this->fail('Setup process failed: '.$setup->getErrorOutput());
    }

    $fixtures = json_decode($setup->getOutput(), true);
    $key = (string) Str::uuid();

    $workerPath = tempnam(sys_get_temp_dir(), 'rec_worker_').'.php';
    file_put_contents($workerPath, concurrencyWorkerScript());

    // إطلاق عدة عمليات فرعية متطابقة في اللحظة نفسها (نفس المفتاح والبيانات).
    $processes = [];
    $concurrentWorkers = 5;

    for ($i = 0; $i < $concurrentWorkers; $i++) {
        $process = new Process(
            [PHP_BINARY, $workerPath, base_path('vendor/autoload.php'), base_path('bootstrap/app.php'), $dbFile, $fixtures['teacher_id'], $fixtures['student_id'], $fixtures['org_id'], $key],
            base_path(),
            concurrencyTestEnv($dbFile),
        );
        $process->setTimeout(120);
        $process->start();
        $processes[] = $process;
    }

    foreach ($processes as $process) {
        $process->wait();
    }

    // 1) لا انهيار ولا استثناء غير معالج في أي عملية فرعية.
    $ids = [];

    foreach ($processes as $index => $process) {
        expect($process->getExitCode())
            ->toBe(0, "Worker {$index} crashed with an unhandled exception: ".$process->getErrorOutput());

        $matches = [];
        $matched = preg_match('/OK:(\d+)/', $process->getOutput(), $matches);

        expect($matched)->toBe(1, "Worker {$index} returned unexpected output: ".$process->getOutput());
        $ids[] = (int) $matches[1];
    }

    // 2) كل العمليات ترجع نفس السجل (سلوك Idempotent) — معرّف واحد مشترك.
    expect(count(array_unique($ids)))->toBe(1);

    // 3) سجل واحد فقط في جدول recitations لهذا المفتاح.
    $pdo = new \PDO('sqlite:'.$dbFile);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM recitations WHERE idempotency_key = ?');
    $stmt->execute([$key]);

    expect((int) $stmt->fetchColumn())->toBe(1);
    expect((int) $pdo->query('SELECT COUNT(*) FROM recitations')->fetchColumn())->toBe(1);

    @unlink($setupPath);
    @unlink($workerPath);
    @unlink($dbFile);
    @unlink($dbFile.'-wal');
    @unlink($dbFile.'-shm');
});

test('stress: many identical sequential requests always return the same recitation record', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForConcurrency($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $teacher = User::factory()->create(['organization_id' => $org->id]);
    $teacher->assignRole($roles['teacher']);
    $circle->update(['teacher_id' => $teacher->id]);

    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
    ]);

    $this->actingAs($teacher);

    $key = (string) Str::uuid();
    $payload = [
        'student_id' => $student->id,
        'type' => 'new',
        'surah_number' => 2,
        'ayah_from' => 1,
        'ayah_to' => 5,
        'rating' => 'excellent',
        'notes' => 'حافظ',
        'idempotency_key' => $key,
    ];

    $ids = [];

    for ($i = 0; $i < 30; $i++) {
        $response = $this->postJson('/recitations', $payload);
        $response->assertCreated();
        $ids[] = $response->json('data.id');
    }

    expect(count(array_unique($ids)))->toBe(1);
    expect(Recitation::withoutGlobalScopes()->where('idempotency_key', $key)->count())->toBe(1);
});
