<?php

use App\Enums\RecitationType;
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

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SurahSeeder::class);
});

/**
 * ينشئ الأدوار والصلاحيات لمنظمة معينة ويعيدها كخريطة role => Role.
 */
function seedRolesForOfflineSync(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

/**
 * تحقق من سيناريو المزامنة عند العودة من وضع Offline (Phase 12 نقطة 5):
 * العميل يخزّن العملية محلياً بـ idempotency_key مولَّد وقت الإدخال، وعند
 * عودة الاتصال يرسلها للخادم. إعادة إرسال نفس idempotency_key (مثلاً بسبب
 * انقطاع الاتصال بعد نجاح فعلي) يجب أن ترجع نفس السجل دون تكرار — هذا هو
 * أساس منع Duplicate Offline Sync (§4 تهديد #14 من وثيقة التحقق المعماري).
 */
test('offline sync: resubmitting the same client-generated idempotency_key after reconnect does not duplicate the record', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForOfflineSync($org->id);

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

    // idempotency_key يُولَّد على العميل وقت الإدخال الفعلي (محاكاة OfflineQueue.enqueue).
    $idempotencyKey = (string) Str::uuid();

    $payload = [
        'student_id' => $student->id,
        'type' => RecitationType::New->value,
        'surah_number' => 2,
        'ayah_from' => 1,
        'ayah_to' => 5,
        'idempotency_key' => $idempotencyKey,
    ];

    // المحاولة الأولى (وقت انقطاع الاتصال ثم عودته — SyncService.syncOne الأول).
    $first = $this->postJson('/recitations', $payload);
    $first->assertCreated();

    // إعادة إرسال بعد إعادة محاولة SyncService (مثلاً بسبب استجابة لم تصل
    // للعميل) — يجب أن يرجع نفس السجل دون إنشاء نسخة مكررة.
    $second = $this->postJson('/recitations', $payload);
    $second->assertCreated();

    expect($first->json('data.id'))->toBe($second->json('data.id'));
    expect(Recitation::withoutGlobalScopes()->where('idempotency_key', $idempotencyKey)->count())->toBe(1);
});

/**
 * كل استجابة HTTP تحمل X-Request-ID (SetRequestId middleware — Phase 14
 * نقطة 1، 2) بصيغة UUID صالحة، لتتبّع الطلب من العميل عند تصحيح مشاكل
 * المزامنة.
 */
test('every response carries a valid UUID X-Request-ID header', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForOfflineSync($org->id);

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
        'idempotency_key' => (string) Str::uuid(),
    ]);

    $response->assertCreated();
    $requestId = $response->headers->get('X-Request-ID');

    expect($requestId)->not->toBeNull();
    expect(Str::isUuid($requestId))->toBeTrue();
});
