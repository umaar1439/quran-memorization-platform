<?php

declare(strict_types=1);

use App\Actions\RecordRecitationAction;
use App\DTOs\RecitationData;
use App\Enums\RecitationType;
use App\Models\Circle;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\Recitation;
use App\Models\StudentProfile;
use App\Models\TeacherAttendance;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SurahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SurahSeeder::class);
});

/**
 * ينشئ الأدوار والصلاحيات لمنظمة معينة ويعيدها كخريطة role => Role.
 */
function seedRolesForRecitation(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

test('teacher records a recitation successfully for a student in their circle', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForRecitation($org->id);

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

    $response = $this->postJson('/recitations', [
        'student_id' => $student->id,
        'type' => RecitationType::New->value,
        'surah_number' => 1,
        'ayah_from' => 1,
        'ayah_to' => 7,
        'rating' => 'excellent',
        'notes' => 'حافظ',
        'idempotency_key' => (string) Str::uuid(),
    ]);

    $response->assertCreated();
    expect(Recitation::withoutGlobalScopes()->count())->toBe(1);

    $recitation = Recitation::withoutGlobalScopes()->first();
    expect($recitation->student_id)->toBe($student->id);
    expect($recitation->teacher_id)->toBe($teacher->id);
});

test('teacher cannot record a recitation for a student outside their circle (IDOR)', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForRecitation($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $teacherCircle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    $otherCircle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $teacher = User::factory()->create(['organization_id' => $org->id]);
    $teacher->assignRole($roles['teacher']);
    $teacherCircle->update(['teacher_id' => $teacher->id]);

    $outsideStudent = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $otherCircle->id,
    ]);

    $this->actingAs($teacher);

    $response = $this->postJson('/recitations', [
        'student_id' => $outsideStudent->id,
        'type' => RecitationType::New->value,
        'surah_number' => 1,
        'ayah_from' => 1,
        'ayah_to' => 7,
        'idempotency_key' => (string) Str::uuid(),
    ]);

    $response->assertForbidden();
    expect(Recitation::withoutGlobalScopes()->count())->toBe(0);
});

test('idempotency: same idempotency_key returns the same record without duplication', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForRecitation($org->id);

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

    $idempotencyKey = (string) Str::uuid();

    $action = app(RecordRecitationAction::class);

    $data = RecitationData::fromArray([
        'student_id' => $student->id,
        'type' => RecitationType::New,
        'surah_number' => 1,
        'ayah_from' => 1,
        'ayah_to' => 7,
        'idempotency_key' => $idempotencyKey,
    ]);

    $first = $action->execute($data, $teacher);
    $second = $action->execute($data, $teacher);

    expect($first->id)->toBe($second->id);
    expect(Recitation::withoutGlobalScopes()->count())->toBe(1);
});

test('invalid quranic range is rejected: surah greater than 114', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForRecitation($org->id);

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

    $response = $this->postJson('/recitations', [
        'student_id' => $student->id,
        'type' => RecitationType::New->value,
        'surah_number' => 115,
        'ayah_from' => 1,
        'ayah_to' => 2,
        'rating' => 'excellent',
        'notes' => 'حافظ',
        'idempotency_key' => (string) Str::uuid(),
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['surah_number']);
});

test('invalid quranic range is rejected: ayah_from greater than ayah_to', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForRecitation($org->id);

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

    $response = $this->postJson('/recitations', [
        'student_id' => $student->id,
        'type' => RecitationType::New->value,
        'surah_number' => 1,
        'ayah_from' => 5,
        'ayah_to' => 2,
        'rating' => 'excellent',
        'notes' => 'حافظ',
        'idempotency_key' => (string) Str::uuid(),
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['ayah_to']);
});

test('attendance is recorded automatically on the first recitation of the day', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForRecitation($org->id);

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

    expect(TeacherAttendance::withoutGlobalScopes()->count())->toBe(0);

    $this->actingAs($teacher);

    $response = $this->postJson('/recitations', [
        'student_id' => $student->id,
        'type' => RecitationType::New->value,
        'surah_number' => 1,
        'ayah_from' => 1,
        'ayah_to' => 7,
        'rating' => 'excellent',
        'notes' => 'حافظ',
        'idempotency_key' => (string) Str::uuid(),
    ]);

    $response->assertCreated();

    $attendance = TeacherAttendance::withoutGlobalScopes()
        ->where('teacher_id', $teacher->id)
        ->first();

    expect($attendance)->not->toBeNull();
    expect($attendance->actions_count)->toBe(1);
    expect($attendance->organization_id)->toBe($org->id);

    // نشاط ثانٍ في نفس اليوم يزيد العداد بدل إنشاء سجل جديد.
    $this->postJson('/recitations', [
        'student_id' => $student->id,
        'type' => RecitationType::New->value,
        'surah_number' => 1,
        'ayah_from' => 1,
        'ayah_to' => 7,
        'rating' => 'excellent',
        'notes' => 'حافظ',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertCreated();

    expect(TeacherAttendance::withoutGlobalScopes()->count())->toBe(1);
    expect($attendance->refresh()->actions_count)->toBe(2);
});

/**
 * يُنشئ سياق تسجيل تسميع كامل (منظمة + معلم + طالب في نفس الحلقة)
 * ويعيد [teacher, student] جاهزين للاستخدام في الاختبارات.
 */
function makeRecitationFixture(): array
{
    $org = Organization::factory()->create();
    $roles = seedRolesForRecitation($org->id);

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

    return [$teacher, $student];
}

test('a recitation with an ayah range within the surah ayah count is accepted', function () {
    [$teacher, $student] = makeRecitationFixture();
    $this->actingAs($teacher);

    // سورة الإخلاص (112) عدد آياتها 4 — نطاق 1-4 صحيح.
    $this->postJson('/recitations', [
        'student_id' => $student->id,
        'type' => RecitationType::New->value,
        'surah_number' => 112,
        'ayah_from' => 1,
        'ayah_to' => 4,
        'rating' => 'excellent',
        'notes' => 'حافظ',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertCreated();
});

test('a recitation with ayah_to exceeding the surah ayah count is rejected', function () {
    [$teacher, $student] = makeRecitationFixture();
    $this->actingAs($teacher);

    // سورة الإخلاص (112) عدد آياتها 4 — آية 5 غير صالحة.
    $this->postJson('/recitations', [
        'student_id' => $student->id,
        'type' => RecitationType::New->value,
        'surah_number' => 112,
        'ayah_from' => 1,
        'ayah_to' => 5,
        'rating' => 'excellent',
        'notes' => 'حافظ',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['ayah_to']);
});

test('a recitation with ayah_from exceeding the surah ayah count is rejected', function () {
    [$teacher, $student] = makeRecitationFixture();
    $this->actingAs($teacher);

    // سورة الإخلاص (112) عدد آياتها 4 — بداية النطاق عند آية 5 غير صالحة.
    $this->postJson('/recitations', [
        'student_id' => $student->id,
        'type' => RecitationType::New->value,
        'surah_number' => 112,
        'ayah_from' => 5,
        'ayah_to' => 5,
        'rating' => 'excellent',
        'notes' => 'حافظ',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['ayah_to']);
});

test('a recitation accepts a note from the fixed options list', function () {
    [$teacher, $student] = makeRecitationFixture();
    $this->actingAs($teacher);

    $this->postJson('/recitations', [
        'student_id' => $student->id,
        'type' => RecitationType::New->value,
        'surah_number' => 1,
        'ayah_from' => 1,
        'ayah_to' => 7,
        'rating' => 'excellent',
        'notes' => 'حافظ',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertCreated();

    expect(Recitation::withoutGlobalScopes()->first()->notes)->toBe('حافظ');
});

test('a recitation accepts a custom note when "اخرى" is selected', function () {
    [$teacher, $student] = makeRecitationFixture();
    $this->actingAs($teacher);

    $this->postJson('/recitations', [
        'student_id' => $student->id,
        'type' => RecitationType::New->value,
        'surah_number' => 1,
        'ayah_from' => 1,
        'ayah_to' => 7,
        'rating' => 'excellent',
        'notes' => 'يحتاج إلى مراجعة أحكام التجويد',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertCreated();

    expect(Recitation::withoutGlobalScopes()->first()->notes)->toBe('يحتاج إلى مراجعة أحكام التجويد');
});

test('a recitation with a note longer than 500 characters is rejected', function () {
    [$teacher, $student] = makeRecitationFixture();
    $this->actingAs($teacher);

    $this->postJson('/recitations', [
        'student_id' => $student->id,
        'type' => RecitationType::New->value,
        'surah_number' => 1,
        'ayah_from' => 1,
        'ayah_to' => 7,
        'rating' => 'excellent',
        'notes' => str_repeat('ا', 501),
        'idempotency_key' => (string) Str::uuid(),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['notes']);
});

test('submitting a recitation without rating and notes is rejected with 422', function () {
    [$teacher, $student] = makeRecitationFixture();
    $this->actingAs($teacher);

    $this->postJson('/recitations', [
        'student_id' => $student->id,
        'type' => RecitationType::New->value,
        'surah_number' => 1,
        'ayah_from' => 1,
        'ayah_to' => 7,
        'idempotency_key' => (string) Str::uuid(),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['rating', 'notes']);
});
