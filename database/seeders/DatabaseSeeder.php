<?php

namespace Database\Seeders;

use App\Models\Circle;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeder تطويري: منظمة تجريبية + مستخدمون بالأدوار الستة + مسجد وحلقة
 * + بيانات القرآن (114 سورة). كلمة المرور: password (لا تُستخدم في production).
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1) الصلاحيات العالمية (بلا team scope).
        $roleSeeder = new RoleAndPermissionSeeder;
        $roleSeeder->run();

        // 2) بيانات القرآن العالمية.
        $this->call(SurahSeeder::class);

        // 3) منظمة تجريبية.
        $organization = Organization::factory()->active()->create([
            'name' => 'جمعية تحفيظ القرآن الكريم النموذجية',
            'slug' => 'demo-organization',
        ]);

        // 4) الأدوار الستة ضمن سياق هذه المنظمة.
        $roles = $roleSeeder->createRolesForOrganization($organization->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        // 5) مسجد وحلقة تجريبيان.
        $mosque = Mosque::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'مسجد النور',
        ]);

        // 6) مستخدم Super Admin (عابر للمنظمات، بلا organization_id).
        $superAdmin = User::factory()->superAdmin()->create([
            'name' => 'مدير النظام العام',
            'email' => 'super-admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $superAdmin->assignRole($roles['super-admin']);

        // 7) مستخدم Org Admin.
        $orgAdmin = User::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'مدير المنظمة',
            'email' => 'org-admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $orgAdmin->assignRole($roles['org-admin']);

        // 8) مستخدم Supervisor.
        $supervisor = User::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'المشرف العام',
            'email' => 'supervisor@example.com',
            'password' => Hash::make('password'),
        ]);
        $supervisor->assignRole($roles['supervisor']);

        // 9) مستخدم Teacher + ملف معلم.
        $teacherUser = User::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'المعلم أحمد',
            'email' => 'teacher@example.com',
            'password' => Hash::make('password'),
        ]);
        $teacherUser->assignRole($roles['teacher']);

        TeacherProfile::query()->create([
            'user_id' => $teacherUser->id,
            'organization_id' => $organization->id,
            'specialization' => 'تجويد وتحفيظ',
            'ijazah_details' => 'إجازة في القراءات العشر',
            'hired_at' => now()->subYears(2),
        ]);

        $circle = Circle::factory()->create([
            'organization_id' => $organization->id,
            'mosque_id' => $mosque->id,
            'teacher_id' => $teacherUser->id,
            'name' => 'حلقة الفرقان',
        ]);

        // 10) مستخدم Student + ملف طالب.
        $studentUser = User::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'الطالب محمد',
            'email' => 'student@example.com',
            'password' => Hash::make('password'),
        ]);
        $studentUser->assignRole($roles['student']);

        $studentProfile = StudentProfile::query()->create([
            'user_id' => $studentUser->id,
            'organization_id' => $organization->id,
            'mosque_id' => $mosque->id,
            'circle_id' => $circle->id,
            'enrollment_number' => 'STU-00001',
            'birth_date' => now()->subYears(10),
            'current_juz' => 5,
            'status' => 'active',
        ]);

        // 11) مستخدم Parent + ربطه بالطالب عبر guardian_student.
        $parentUser = User::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'ولي الأمر خالد',
            'email' => 'parent@example.com',
            'password' => Hash::make('password'),
        ]);
        $parentUser->assignRole($roles['parent']);

        $guardian = \App\Models\Guardian::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $parentUser->id,
            'name' => $parentUser->name,
            'phone' => $parentUser->phone,
            'relationship_type' => 'أب',
        ]);

        $guardian->students()->attach($studentProfile->id, [
            'organization_id' => $organization->id,
            'permissions' => json_encode([
                'view_recitations' => true,
                'view_exam_results' => true,
                'view_attendance' => false,
                'receive_notifications' => true,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
