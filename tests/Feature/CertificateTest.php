<?php

declare(strict_types=1);

use App\Actions\GenerateCertificateAction;
use App\Enums\CertificateStatus;
use App\Jobs\GenerateCertificateJob;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\FeatureFlag;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\StudentProfile;
use App\Models\User;
use App\Support\FeatureFlagCache;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function seedRolesForCertificate(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

function enableCertificatesFlag(int $organizationId, bool $enabled = true): void
{
    FeatureFlag::withoutGlobalScopes()->create([
        'organization_id' => $organizationId,
        'key' => 'certificates.enabled',
        'enabled' => $enabled,
    ]);
}

test('an org-admin issues a certificate successfully when feature flag is enabled', function () {
    Storage::fake('local');

    $org = Organization::factory()->create();
    $roles = seedRolesForCertificate($org->id);
    enableCertificatesFlag($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $student = StudentProfile::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    $this->actingAs($orgAdmin);

    $response = $this->postJson('/certificates', [
        'student_id' => $student->id,
        'certificate_type' => 'إتمام حفظ',
    ]);

    $response->assertStatus(202);
    $response->assertJson(['message' => 'جاري توليد الشهادة في الخلفية، سيتم إعلامك فور جاهزيتها.']);

    $certificate = Certificate::withoutGlobalScopes()->first();
    expect($certificate->status)->toBe(CertificateStatus::Issued);
    expect($certificate->verification_code)->not->toBeEmpty();

    Storage::disk('local')->assertExists($certificate->getRawOriginal('file_path'));

    $auditLog = AuditLog::withoutGlobalScopes()
        ->where('event', 'certificate.issued')
        ->where('auditable_id', $certificate->id)
        ->first();
    expect($auditLog)->not->toBeNull();
});

test('certificate generation is rejected when the feature flag is disabled', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForCertificate($org->id);
    enableCertificatesFlag($org->id, false);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $student = StudentProfile::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    $this->actingAs($orgAdmin);

    $response = $this->postJson('/certificates', [
        'student_id' => $student->id,
        'certificate_type' => 'إتمام حفظ',
    ]);

    $response->assertForbidden();
});

test('a teacher cannot generate a certificate (role restricted to org-admin)', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForCertificate($org->id);
    enableCertificatesFlag($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $student = StudentProfile::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $teacher = User::factory()->create(['organization_id' => $org->id]);
    $teacher->assignRole($roles['teacher']);

    $this->actingAs($teacher);

    $response = $this->postJson('/certificates', [
        'student_id' => $student->id,
        'certificate_type' => 'إتمام حفظ',
    ]);

    $response->assertForbidden();
});

test('public verification endpoint returns valid for an issued certificate without exposing personal data', function () {
    Storage::fake('local');

    $org = Organization::factory()->create();
    $roles = seedRolesForCertificate($org->id);
    enableCertificatesFlag($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $student = StudentProfile::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    $this->actingAs($orgAdmin);
    $storeResponse = $this->postJson('/certificates', [
        'student_id' => $student->id,
        'certificate_type' => 'إتمام حفظ',
    ]);

    $code = $storeResponse->json('data.verification_code');

    // بدون auth إطلاقاً — نقطة عامة.
    $this->app['auth']->forgetGuards();
    $verifyResponse = $this->getJson("/certificates/verify/{$code}");

    $verifyResponse->assertOk();
    $verifyResponse->assertJson(['valid' => true]);
    $verifyResponse->assertJsonMissing(['student_id']);
    $verifyResponse->assertJsonMissing(['file_path']);
});

test('public verification endpoint returns invalid for an unknown code', function () {
    $response = $this->getJson('/certificates/verify/unknown-code-xyz');

    $response->assertOk();
    $response->assertJson(['valid' => false]);
});

test('the org-admin who issued a certificate can download it', function () {
    Storage::fake('local');

    $org = Organization::factory()->create();
    $roles = seedRolesForCertificate($org->id);
    enableCertificatesFlag($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $student = StudentProfile::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    $this->actingAs($orgAdmin);
    $storeResponse = $this->postJson('/certificates', [
        'student_id' => $student->id,
        'certificate_type' => 'إتمام حفظ',
    ]);
    $certificateId = $storeResponse->json('data.id');

    $response = $this->get("/certificates/{$certificateId}/download");
    $response->assertOk();
});

test('a student cannot download another students certificate (tenant/ownership check)', function () {
    Storage::fake('local');

    $org = Organization::factory()->create();
    $roles = seedRolesForCertificate($org->id);
    enableCertificatesFlag($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $studentA = StudentProfile::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    $studentB = StudentProfile::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    $this->actingAs($orgAdmin);
    $storeResponse = $this->postJson('/certificates', [
        'student_id' => $studentA->id,
        'certificate_type' => 'إتمام حفظ',
    ]);
    $certificateId = $storeResponse->json('data.id');

    $studentB->user->assignRole($roles['student']);
    $this->actingAs($studentB->user);

    $response = $this->get("/certificates/{$certificateId}/download");
    $response->assertForbidden();
});

test('certificate generation is queued and returns immediately with a pending certificate', function () {
    Storage::fake('local');
    Queue::fake();

    $org = Organization::factory()->create();
    $roles = seedRolesForCertificate($org->id);
    enableCertificatesFlag($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $student = StudentProfile::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    $this->actingAs($orgAdmin);

    $response = $this->postJson('/certificates', [
        'student_id' => $student->id,
        'certificate_type' => 'إتمام حفظ',
    ]);

    $response->assertStatus(202);
    $response->assertJson(['message' => 'جاري توليد الشهادة في الخلفية، سيتم إعلامك فور جاهزيتها.']);

    Queue::assertPushed(GenerateCertificateJob::class, function (GenerateCertificateJob $job) use ($orgAdmin) {
        return $job->actorId === $orgAdmin->id;
    });

    $certificate = Certificate::withoutGlobalScopes()->first();
    expect($certificate->status)->toBe(CertificateStatus::Pending);
    expect($certificate->getRawOriginal('file_path'))->toBeNull();
});

test('the generation job re-verifies the feature flag and fails if it was disabled after request', function () {
    Storage::fake('local');
    Queue::fake();

    $org = Organization::factory()->create();
    $roles = seedRolesForCertificate($org->id);
    enableCertificatesFlag($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $student = StudentProfile::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    $this->actingAs($orgAdmin);

    $certificate = app(GenerateCertificateAction::class)->execute([
        'student_id' => $student->id,
        'certificate_type' => 'إتمام حفظ',
    ], $orgAdmin);

    expect($certificate->status)->toBe(CertificateStatus::Pending);

    // تعطيل الـ flag بين الطلب وتنفيذ الـ job (دفاع الطبقة الثانية).
    FeatureFlag::withoutGlobalScopes()
        ->where('organization_id', $org->id)
        ->where('key', 'certificates.enabled')
        ->update(['enabled' => false]);

    // إبطال الذاكرة كما يفعل UpdateFeatureFlagAction عند تبديل العلم من الواجهة،
    // وإلا قرأ الـ job القيمة المخبأة القديمة (وهذا سلوك مقصود وليس خللاً).
    app(FeatureFlagCache::class)->forget($org->id);

    // handle() تستقبل تبعياتها بحقن الطريقة (Method Injection) كأي Job يُشغّله الطابور.
    app()->call([new GenerateCertificateJob($certificate->id, $orgAdmin->id), 'handle']);

    expect($certificate->refresh()->status)->toBe(CertificateStatus::Failed);
});
