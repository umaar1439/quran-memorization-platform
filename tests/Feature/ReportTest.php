<?php

declare(strict_types=1);

use App\Enums\ReportStatus;
use App\Jobs\GenerateReportJob;
use App\Models\Organization;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function seedRolesForReport(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

test('a supervisor requests a report and it is queued with a GenerateReportJob dispatched', function () {
    Queue::fake();

    $org = Organization::factory()->create();
    $roles = seedRolesForReport($org->id);

    $supervisor = User::factory()->create(['organization_id' => $org->id]);
    $supervisor->assignRole($roles['supervisor']);

    $this->actingAs($supervisor);

    $response = $this->postJson('/reports', [
        'report_type' => 'circle_summary',
    ]);

    $response->assertCreated();

    $report = Report::withoutGlobalScopes()->first();
    expect($report->status)->toBe(ReportStatus::Queued);
    expect($report->organization_id)->toBe($org->id);

    Queue::assertPushed(GenerateReportJob::class, fn ($job) => $job->reportId === $report->id);
});

test('a student cannot request a report (missing reports.generate permission)', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForReport($org->id);

    $student = User::factory()->create(['organization_id' => $org->id]);
    $student->assignRole($roles['student']);

    $this->actingAs($student);

    $response = $this->postJson('/reports', [
        'report_type' => 'circle_summary',
    ]);

    $response->assertForbidden();
});

test('GenerateReportJob completes and stores a downloadable file', function () {
    Storage::fake('local');

    $org = Organization::factory()->create();
    $roles = seedRolesForReport($org->id);

    $supervisor = User::factory()->create(['organization_id' => $org->id]);
    $supervisor->assignRole($roles['supervisor']);

    $report = Report::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'requested_by' => $supervisor->id,
        'report_type' => 'circle_summary',
        'status' => ReportStatus::Queued,
    ]);

    // الحاوية تحقن تبعيات handle() كما يفعل الطابور تماماً.
    app()->call([new GenerateReportJob($report->id, $supervisor->id), 'handle']);

    $report->refresh();
    expect($report->status)->toBe(ReportStatus::Completed);
    expect($report->expires_at)->not->toBeNull();
    Storage::disk('local')->assertExists($report->getRawOriginal('file_path'));

    $this->actingAs($supervisor);
    $response = $this->get("/reports/{$report->id}/download");
    $response->assertOk();
});

test('GenerateReportJob fails if the requesting user no longer has permission (defense in depth)', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForReport($org->id);

    $supervisor = User::factory()->create(['organization_id' => $org->id]);
    $supervisor->assignRole($roles['supervisor']);

    $report = Report::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'requested_by' => $supervisor->id,
        'report_type' => 'circle_summary',
        'status' => ReportStatus::Queued,
    ]);

    // يفقد المستخدم الصلاحية بعد الطلب وقبل التنفيذ.
    $supervisor->syncPermissions([]);
    $supervisor->syncRoles([]);

    // الحاوية تحقن تبعيات handle() كما يفعل الطابور تماماً.
    app()->call([new GenerateReportJob($report->id, $supervisor->id), 'handle']);

    $report->refresh();
    expect($report->status)->toBe(ReportStatus::Failed);
});

test('downloading an expired report returns 410 gone', function () {
    Storage::fake('local');

    $org = Organization::factory()->create();
    $roles = seedRolesForReport($org->id);

    $supervisor = User::factory()->create(['organization_id' => $org->id]);
    $supervisor->assignRole($roles['supervisor']);

    Storage::disk('local')->put("reports/{$org->id}/999.json", '{}');

    $report = Report::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'requested_by' => $supervisor->id,
        'report_type' => 'circle_summary',
        'status' => ReportStatus::Completed,
        'file_path' => "reports/{$org->id}/999.json",
        'expires_at' => now()->subDay(),
    ]);

    $this->actingAs($supervisor);
    $response = $this->get("/reports/{$report->id}/download");
    $response->assertStatus(410);
});

test('a user from another organization cannot download a report (tenant isolation)', function () {
    Storage::fake('local');

    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    seedRolesForReport($orgA->id);
    $rolesB = seedRolesForReport($orgB->id);

    $requester = User::factory()->create(['organization_id' => $orgA->id]);

    $report = Report::withoutGlobalScopes()->create([
        'organization_id' => $orgA->id,
        'requested_by' => $requester->id,
        'report_type' => 'circle_summary',
        'status' => ReportStatus::Completed,
        'file_path' => "reports/{$orgA->id}/1.json",
    ]);

    $userB = User::factory()->create(['organization_id' => $orgB->id]);
    $userB->assignRole($rolesB['supervisor']);

    $this->actingAs($userB);
    $response = $this->get("/reports/{$report->id}/download");
    $response->assertNotFound();
});
