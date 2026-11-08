<?php

declare(strict_types=1);

use App\Models\Circle;
use App\Models\Mosque;
use App\Models\Organization;
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

function seedRolesForAttendance(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

test('a teacher records separate attendance per mosque when teaching in multiple mosques in the same day', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForAttendance($org->id);

    $mosqueA = Mosque::factory()->create(['organization_id' => $org->id]);
    $mosqueB = Mosque::factory()->create(['organization_id' => $org->id]);

    $circleA = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosqueA->id]);
    $circleB = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosqueB->id]);

    $teacher = User::factory()->create(['organization_id' => $org->id]);
    $teacher->assignRole($roles['teacher']);
    $circleA->update(['teacher_id' => $teacher->id]);
    $circleB->update(['teacher_id' => $teacher->id]);

    $studentA = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosqueA->id,
        'circle_id' => $circleA->id,
    ]);
    $studentB = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosqueB->id,
        'circle_id' => $circleB->id,
    ]);

    $this->actingAs($teacher);

    // نشاط أول في المسجد A.
    $this->postJson('/recitations', [
        'student_id' => $studentA->id,
        'type' => 'new',
        'surah_number' => 2,
        'ayah_from' => 1,
        'ayah_to' => 5,
        'rating' => 'excellent',
        'notes' => 'حافظ',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertCreated();

    // نشاط ثانٍ في المسجد B (نفس اليوم) — يجب ألا يتعارض مع القيد الفريد.
    $this->postJson('/recitations', [
        'student_id' => $studentB->id,
        'type' => 'new',
        'surah_number' => 2,
        'ayah_from' => 1,
        'ayah_to' => 5,
        'rating' => 'excellent',
        'notes' => 'حافظ',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertCreated();

    // سجلا حضور مستقلان (واحد لكل مسجد).
    expect(TeacherAttendance::withoutGlobalScopes()->count())->toBe(2);

    $attendanceA = TeacherAttendance::withoutGlobalScopes()
        ->where('teacher_id', $teacher->id)
        ->where('mosque_id', $mosqueA->id)
        ->first();
    $attendanceB = TeacherAttendance::withoutGlobalScopes()
        ->where('teacher_id', $teacher->id)
        ->where('mosque_id', $mosqueB->id)
        ->first();

    expect($attendanceA)->not->toBeNull();
    expect($attendanceA->actions_count)->toBe(1);
    expect($attendanceA->mosque_id)->toBe($mosqueA->id);

    expect($attendanceB)->not->toBeNull();
    expect($attendanceB->actions_count)->toBe(1);
    expect($attendanceB->mosque_id)->toBe($mosqueB->id);

    // نشاط إضافي في المسجد A يزيد عداد المسجد A فقط، دون إنشاء سجل جديد.
    $this->postJson('/recitations', [
        'student_id' => $studentA->id,
        'type' => 'new',
        'surah_number' => 3,
        'ayah_from' => 1,
        'ayah_to' => 5,
        'rating' => 'excellent',
        'notes' => 'حافظ',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertCreated();

    expect(TeacherAttendance::withoutGlobalScopes()->count())->toBe(2);
    expect($attendanceA->refresh()->actions_count)->toBe(2);
    expect($attendanceB->refresh()->actions_count)->toBe(1);
});
