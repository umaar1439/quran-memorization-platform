<?php

declare(strict_types=1);

use App\Models\Circle;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function seedRolesForQueryCount(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

/**
 * يشغّل الطلب ويعيد عدد استعلامات SQL التي نُفِّذت خلاله.
 *
 * @param  Closure(): mixed  $callback
 */
function countQueriesFor(Closure $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $callback();

    $count = count(DB::getQueryLog());

    DB::disableQueryLog();

    return $count;
}

/**
 * الحارس الحقيقي ضد N+1: عدد الاستعلامات يجب ألا ينمو مع عدد الصفوف.
 * مع Model::shouldBeStrict() أي lazy load كان سيُسقط الطلب بـ 500 أصلاً،
 * لكن هذا الاختبار يمنع أيضاً الانحدار "الصامت" عبر eager loads زائدة لكل صف.
 */
test('the students list does not grow its query count with the number of rows', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForQueryCount($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    $this->actingAs($admin);

    StudentProfile::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id, 'circle_id' => $circle->id]);

    $baseline = countQueriesFor(fn () => $this->get(route('admin.students.index'))->assertOk());

    StudentProfile::factory()->count(8)->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
    ]);

    $withManyRows = countQueriesFor(fn () => $this->get(route('admin.students.index'))->assertOk());

    // سماحية صغيرة للفروق المشروعة (مثلاً استعلام العدّاد)، لا نمو خطي.
    expect($withManyRows)->toBeLessThanOrEqual($baseline + 2);
});

test('the teachers list does not grow its query count with the number of rows', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForQueryCount($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    $this->actingAs($admin);

    $firstTeacher = User::factory()->create(['organization_id' => $org->id]);
    TeacherProfile::query()->create(['user_id' => $firstTeacher->id, 'organization_id' => $org->id]);

    $baseline = countQueriesFor(fn () => $this->get(route('admin.teachers.index'))->assertOk());

    foreach (range(1, 6) as $index) {
        $teacher = User::factory()->create(['organization_id' => $org->id]);
        TeacherProfile::query()->create(['user_id' => $teacher->id, 'organization_id' => $org->id]);
        $circle->update(['teacher_id' => $teacher->id]);
    }

    $withManyRows = countQueriesFor(fn () => $this->get(route('admin.teachers.index'))->assertOk());

    expect($withManyRows)->toBeLessThanOrEqual($baseline + 2);
});
