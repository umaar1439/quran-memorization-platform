<?php

use App\Enums\ReportStatus;
use App\Models\Organization;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('org-admin can download their own report through the full middleware stack', function () {
    Storage::fake('local');
    Storage::disk('local')->put('reports/1/1.json', '{}');

    $org = Organization::factory()->create();
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();
    $roles = $seeder->createRolesForOrganization($org->id);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    $report = Report::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'requested_by' => $admin->id,
        'report_type' => 'performance',
        'status' => ReportStatus::Completed,
        'file_path' => 'reports/1/1.json',
        'expires_at' => now()->addDay(),
    ]);

    $this->post('/login', ['email' => $admin->email, 'password' => 'password']);

    $this->get("/reports/{$report->id}/download")->assertOk();
});
