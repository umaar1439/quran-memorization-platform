<?php

declare(strict_types=1);

use App\Actions\TransferStudentAction;
use App\Enums\RecitationType;
use App\Models\AuditLog;
use App\Models\Circle;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\Recitation;
use App\Models\StudentProfile;
use App\Models\StudentTransfer;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * ينشئ الأدوار والصلاحيات لمنظمة معينة ويعيدها كخريطة role => Role.
 */
function seedRolesForTransfer(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

test('student is transferred successfully and circle_id is updated', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForTransfer($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $fromCircle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    $toCircle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $fromCircle->id,
    ]);

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    $this->actingAs($orgAdmin);

    $response = $this->postJson('/student-transfers', [
        'student_id' => $student->id,
        'to_mosque_id' => $mosque->id,
        'to_circle_id' => $toCircle->id,
        'reason' => 'انتقال داخل نفس المسجد',
    ]);

    $response->assertCreated();

    $student->refresh();
    expect($student->circle_id)->toBe($toCircle->id);
    expect($student->mosque_id)->toBe($mosque->id);

    expect(StudentTransfer::withoutGlobalScopes()->count())->toBe(1);
    $transfer = StudentTransfer::withoutGlobalScopes()->first();
    expect($transfer->from_circle_id)->toBe($fromCircle->id);
    expect($transfer->to_circle_id)->toBe($toCircle->id);
});

test('old recitation records keep their original circle_id after a transfer (no rewrite)', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForTransfer($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $fromCircle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    $toCircle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $teacher = User::factory()->create(['organization_id' => $org->id]);
    $teacher->assignRole($roles['teacher']);
    $fromCircle->update(['teacher_id' => $teacher->id]);

    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $fromCircle->id,
    ]);

    $recitation = Recitation::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'student_id' => $student->id,
        'teacher_id' => $teacher->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $fromCircle->id,
        'type' => RecitationType::New,
        'surah_number' => 1,
        'ayah_from' => 1,
        'ayah_to' => 7,
        'performed_at' => now(),
        'idempotency_key' => (string) Str::uuid(),
    ]);

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    $action = app(TransferStudentAction::class);
    $action->execute([
        'student_id' => $student->id,
        'to_mosque_id' => $mosque->id,
        'to_circle_id' => $toCircle->id,
    ], $orgAdmin);

    $student->refresh();
    $recitation->refresh();

    expect($student->circle_id)->toBe($toCircle->id);
    // السجل التاريخي يبقى مرتبطاً بالحلقة القديمة كما حدث فعلياً.
    expect($recitation->circle_id)->toBe($fromCircle->id);
});

test('transfer to another organization is rejected', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $rolesA = seedRolesForTransfer($orgA->id);
    seedRolesForTransfer($orgB->id);

    $mosqueA = Mosque::factory()->create(['organization_id' => $orgA->id]);
    $circleA = Circle::factory()->create(['organization_id' => $orgA->id, 'mosque_id' => $mosqueA->id]);

    $mosqueB = Mosque::factory()->create(['organization_id' => $orgB->id]);

    $student = StudentProfile::factory()->create([
        'organization_id' => $orgA->id,
        'mosque_id' => $mosqueA->id,
        'circle_id' => $circleA->id,
    ]);

    // إعادة ضبط سياق Team إلى orgA قبل تعيين الدور (seedRolesForTransfer(orgB)
    // أعلاه غيّر السياق الحالي إلى orgB — انظر §6.2 من وثيقة التحقق المعماري).
    app(PermissionRegistrar::class)->setPermissionsTeamId($orgA->id);

    $orgAdminA = User::factory()->create(['organization_id' => $orgA->id]);
    $orgAdminA->assignRole($rolesA['org-admin']);

    $this->actingAs($orgAdminA);

    $response = $this->postJson('/student-transfers', [
        'student_id' => $student->id,
        'to_mosque_id' => $mosqueB->id,
    ]);

    // يُرفض عبر Form Request validation (exists rule مقيّدة بمنظمة المستخدم).
    $response->assertUnprocessable();
    expect(StudentTransfer::withoutGlobalScopes()->count())->toBe(0);
});

test('an audit_log record is created upon transfer', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForTransfer($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $fromCircle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    $toCircle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $fromCircle->id,
    ]);

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    $this->actingAs($orgAdmin);

    $this->postJson('/student-transfers', [
        'student_id' => $student->id,
        'to_mosque_id' => $mosque->id,
        'to_circle_id' => $toCircle->id,
    ])->assertCreated();

    $log = AuditLog::withoutGlobalScopes()
        ->where('auditable_type', StudentProfile::class)
        ->where('auditable_id', $student->id)
        ->where('event', 'student.transferred')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->organization_id)->toBe($org->id);
    expect($log->user_id)->toBe($orgAdmin->id);
    expect($log->new_values['circle_id'])->toBe($toCircle->id);
});
