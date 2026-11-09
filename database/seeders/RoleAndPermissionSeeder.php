<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * ينشئ الصلاحيات (عالمية، بلا team scope) والأدوار الستة (نطاق كل منظمة).
 */
class RoleAndPermissionSeeder extends Seeder
{
    /**
     * قائمة الصلاحيات المطلوبة بصيغة {resource}.{action}.
     */
    public const PERMISSIONS = [
        'users.view', 'users.create', 'users.update', 'users.delete',

        // إدارة الأدوار والصلاحيات (شاشة /admin/roles) — منفصلة عن users.*
        // حتى يمكن منح «تعديل الصلاحيات» لمن لا يدير المستخدمين.
        'roles.view', 'roles.manage',

        'teachers.view', 'teachers.create', 'teachers.update', 'teachers.delete',
        'circles.view', 'circles.manage',
        'students.view', 'students.manage', 'students.transfer',
        'recitations.create', 'recitations.view', 'recitations.update',
        'attendance.view', 'attendance.manage',
        'reports.generate', 'reports.download',
        'certificates.generate',
        'settings.manage',
        'terminology.manage',
        'features.manage',
        'exams.view', 'exams.create', 'exams.approve',
    ];

    /**
     * الأدوار الستة للنظام.
     */
    public const ROLES = [
        'super-admin',
        'org-admin',
        'supervisor',
        'teacher',
        'student',
        'parent',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    /**
     * ينشئ الأدوار الستة ضمن سياق منظمة محددة (Team-Scoped عبر Spatie Teams)
     * ويربط الصلاحيات المناسبة بكل دور.
     */
    public function createRolesForOrganization(int $organizationId): array
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($organizationId);

        $roles = [];

        foreach (self::ROLES as $roleName) {
            $roles[$roleName] = Role::findOrCreate($roleName, 'web');
        }

        // super-admin: كل الصلاحيات (عابر للمنظمات بالتصميم رغم وجود صف team-scoped هنا للاختبار).
        $roles['super-admin']->syncPermissions(Permission::all());

        // org-admin: كل الصلاحيات ضمن منظمته.
        $roles['org-admin']->syncPermissions(Permission::all());

        $roles['supervisor']->syncPermissions([
            'teachers.view', 'teachers.create', 'teachers.update', 'teachers.delete',
            'circles.view', 'circles.manage',
            'students.view', 'students.manage', 'students.transfer',
            'recitations.view',
            'attendance.view', 'attendance.manage',
            'reports.generate', 'reports.download',
            'exams.view', 'exams.create',
        ]);

        $roles['teacher']->syncPermissions([
            'circles.view',
            'students.view',
            'recitations.create', 'recitations.view', 'recitations.update',
            'attendance.view', 'attendance.manage',
            'exams.view',
        ]);

        $roles['student']->syncPermissions([
            'recitations.view',
        ]);

        $roles['parent']->syncPermissions([
            'recitations.view',
        ]);

        return $roles;
    }
}
