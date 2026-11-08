<?php

declare(strict_types=1);

use App\Models\Circle;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\StudentProfile;
use App\Models\Surah;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user only sees mosques belonging to their own organization', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $mosqueA = Mosque::factory()->create(['organization_id' => $orgA->id]);
    Mosque::factory()->create(['organization_id' => $orgB->id]);

    $userA = User::factory()->create(['organization_id' => $orgA->id]);

    $this->actingAs($userA);

    $visibleMosques = Mosque::all();

    expect($visibleMosques)->toHaveCount(1);
    expect($visibleMosques->first()->id)->toBe($mosqueA->id);
});

test('a user cannot fetch a specific record belonging to another organization by id', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $mosqueB = Mosque::factory()->create(['organization_id' => $orgB->id]);

    $userA = User::factory()->create(['organization_id' => $orgA->id]);
    $this->actingAs($userA);

    expect(Mosque::find($mosqueB->id))->toBeNull();
});

test('creating a record without an explicit organization_id inherits it from the authenticated user', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);

    $this->actingAs($user);

    $mosque = Mosque::create([
        'name' => 'مسجد تجريبي',
        'status' => 'active',
    ]);

    expect($mosque->organization_id)->toBe($org->id);
});

test('super-admin role bypasses the tenant global scope and sees records from all organizations', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    Mosque::factory()->create(['organization_id' => $orgA->id]);
    Mosque::factory()->create(['organization_id' => $orgB->id]);

    $roleSeeder = new RoleAndPermissionSeeder;
    $roleSeeder->run();
    $roles = $roleSeeder->createRolesForOrganization($orgA->id);

    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole($roles['super-admin']);

    $this->actingAs($superAdmin);

    expect(Mosque::all())->toHaveCount(2);
});

test('reference data tables like surahs are never scoped by organization', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    $this->actingAs($user);

    Surah::query()->create([
        'number' => 1,
        'name_arabic' => 'الفاتحة',
        'name_english' => 'Al-Faatiha',
        'revelation_type' => 'meccan',
        'ayah_count' => 7,
    ]);

    expect(Surah::count())->toBe(1);
});

test('nested tenant-owned relations stay consistent within the same organization', function () {
    $org = Organization::factory()->create();
    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
    ]);
    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
    ]);

    expect($student->organization_id)->toBe($org->id);
    expect($student->mosque_id)->toBe($mosque->id);
    expect($student->circle_id)->toBe($circle->id);
    expect($circle->organization_id)->toBe($org->id);
});
