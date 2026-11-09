<?php

declare(strict_types=1);

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CircleController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamResultController;
use App\Http\Controllers\FeatureFlagController;
use App\Http\Controllers\MosqueController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ParentDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecitationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentExamsController;
use App\Http\Controllers\StudentGoalController;
use App\Http\Controllers\StudentTransferController;
use App\Http\Controllers\TeacherAttendanceController;
use App\Http\Controllers\TeacherCircleController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// لا Closures في مسارات الإنتاج حتى يعمل `php artisan route:cache`.
Route::get('/', [PageController::class, 'welcome']);

Route::get('/dashboard', [PageController::class, 'dashboard'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/recitations', [RecitationController::class, 'index'])->name('recitations.index');
    Route::post('/recitations', [RecitationController::class, 'store'])->name('recitations.store');
    Route::get('/recitations/{recitation}', [RecitationController::class, 'show'])->name('recitations.show');

    Route::get('/teacher-attendances', [TeacherAttendanceController::class, 'index'])->name('teacher-attendances.index');

    Route::get('/student-transfers', [StudentTransferController::class, 'index'])->name('student-transfers.index');
    Route::post('/student-transfers', [StudentTransferController::class, 'store'])->name('student-transfers.store');

    // حضور الطلاب اليومي: كتابة فقط هنا — التفويض عبر AttendancePolicy في
    // FormRequest والـ Action (لا حصر بالدور حتى لا يُرفض المشرف بخطأ مضلل).
    Route::post('/attendance', [StudentAttendanceController::class, 'store'])->name('attendance.store');
    Route::post('/attendance/bulk', [StudentAttendanceController::class, 'bulk'])->name('attendance.bulk');

    // Phase 8: الأهداف والتحليلات.
    Route::get('/student-goals', [StudentGoalController::class, 'index'])->name('student-goals.index');
    Route::post('/student-goals', [StudentGoalController::class, 'store'])->name('student-goals.store');
    Route::patch('/student-goals/{student_goal}', [StudentGoalController::class, 'update'])->name('student-goals.update');

    Route::get('/student-dashboard/{student}', [StudentDashboardController::class, 'index'])->name('student-dashboard.index');

    // عرض ملف + لوحة تحكم طالب محدد (مدير/مشرف/معلم) مع تفويض الصلاحية لـ StudentProfilePolicy.
    Route::get('/students/{student}', [StudentDashboardController::class, 'show'])->name('students.show');

    // Phase 9: الامتحانات.
    Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
    Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');
    Route::get('/exams/{exam}', [ExamController::class, 'show'])->name('exams.show');
    Route::patch('/exams/{exam}', [ExamController::class, 'update'])->name('exams.update');

    Route::post('/exam-results', [ExamResultController::class, 'store'])->name('exam-results.store');
    Route::post('/exam-results/{exam_result}/approve', [ExamResultController::class, 'approve'])->name('exam-results.approve');
    Route::post('/exam-results/{exam_result}/correct', [ExamResultController::class, 'correct'])->name('exam-results.correct');

    // Phase 10: الشهادات.
    Route::middleware('feature:certificates.enabled')->group(function () {
        Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
        Route::post('/certificates', [CertificateController::class, 'store'])
            ->middleware('throttle:certificate-generation')
            ->name('certificates.store');
    });
    Route::get('/certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');

    // Phase 11: التقارير.
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports', [ReportController::class, 'store'])
        ->middleware('throttle:report-generation')
        ->name('reports.store');
    Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');

    // Phase 14: سجلات التدقيق — read-only، org-admin فقط (Policy).
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // Phase 15: صفحات الحلقات — التفويض هنا بالصلاحية لا بالدور.
    // السبب: CirclePolicy::viewAny هي المصدر الوحيد للتفويض (circles.view)،
    // وكان حصر المسار بالأدوار يعارضها ويرفض المعلم رغم امتلاكه الصلاحية
    // برسالة مضللة "User does not have the right roles". القراءة بـ
    // circles.view، والكتابة بـ circles.manage (نفس شرط السياسة/FormRequest).
    Route::middleware('can:circles.view')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/circles', [CircleController::class, 'page'])->name('circles.index');
        Route::get('/circles/{circle}', [CircleController::class, 'show'])->name('circles.show');
    });

    Route::middleware('can:circles.manage')->prefix('admin')->name('admin.')->group(function () {
        Route::post('/circles', [CircleController::class, 'store'])->name('circles.store');
        Route::patch('/circles/{circle}', [CircleController::class, 'update'])->name('circles.update');
        Route::delete('/circles/{circle}', [CircleController::class, 'destroy'])->name('circles.destroy');
    });

    // إدارة الأدوار والصلاحيات — بالصلاحية لا بالدور (roles.view/roles.manage)،
    // وRolePolicy تفرض حدود المنظمة وحماية دور super-admin.
    Route::middleware('can:roles.view')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/roles', [RoleController::class, 'page'])->name('roles.index');
    });

    Route::middleware('can:roles.manage')->prefix('admin')->name('admin.')->group(function () {
        Route::patch('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });

    // Phase 15: صفحات Inertia لكل الأدوار — استخدام '{controller}::page'.
    Route::middleware('role:org-admin|supervisor|super-admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'page'])->name('dashboard');

        Route::get('/mosques', [MosqueController::class, 'page'])->name('mosques.index');
        Route::post('/mosques', [MosqueController::class, 'store'])->name('mosques.store');
        Route::patch('/mosques/{mosque}', [MosqueController::class, 'update'])->name('mosques.update');
        Route::delete('/mosques/{mosque}', [MosqueController::class, 'destroy'])->name('mosques.destroy');

        Route::get('/users', [UserController::class, 'page'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // إدارة المعلمين (منفصلة عن إدارة المستخدمين).
        Route::get('/teachers', [TeacherController::class, 'page'])->name('teachers.index');
        Route::post('/teachers', [TeacherController::class, 'store'])->name('teachers.store');
        Route::patch('/teachers/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
        Route::delete('/teachers/{teacher}', [TeacherController::class, 'destroy'])->name('teachers.destroy');

        // إدارة الطلاب (منفصلة عن إدارة المستخدمين).
        Route::get('/students', [StudentController::class, 'page'])->name('students.index');
        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        Route::patch('/students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

        Route::get('/settings', [SettingsController::class, 'page'])->name('settings.index');
        Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');

        Route::get('/feature-flags', [FeatureFlagController::class, 'page'])->name('feature-flags.index');
        Route::patch('/feature-flags/{flag}', [FeatureFlagController::class, 'update'])->name('feature-flags.update');

        Route::get('/exams', [ExamController::class, 'page'])->name('exams.page');
        Route::get('/exams/{exam}/results', [ExamResultController::class, 'page'])->name('exams.results');

        Route::get('/student-transfers', [StudentTransferController::class, 'page'])->name('student-transfers.page');
        Route::get('/reports', [ReportController::class, 'page'])->name('reports.page');
        Route::get('/audit-logs-page', [AuditLogController::class, 'page'])->name('audit-logs.page');

        // حضور الطلاب: ورقة يومية + سجل مفصّل — التفويض بـ AttendancePolicy::viewAny.
        Route::get('/attendance', [StudentAttendanceController::class, 'page'])->name('attendance.index');
    });

    Route::middleware('role:teacher')->prefix('teacher')->name('teacher.')->group(function () {
        Route::get('/dashboard', [TeacherCircleController::class, 'page'])->name('dashboard');
        Route::get('/quick-recitation', [RecitationController::class, 'page'])->name('quick-recitation');
        Route::get('/my-circle', [TeacherCircleController::class, 'page'])->name('my-circle');

        // "الحضور" في التنقل تعني ورقة حضور الطلاب اليومية (الوحدة الفعلية)،
        // وسجل نشاط المعلم المُشتق بقي متاحاً على /teacher/activity.
        Route::get('/attendance', [StudentAttendanceController::class, 'teacherPage'])->name('attendance.page');
        Route::get('/activity', [TeacherAttendanceController::class, 'page'])->name('activity.page');
    });

    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'page'])->name('dashboard');
        Route::get('/goals', [StudentGoalController::class, 'page'])->name('goals');
        Route::get('/exams', [StudentExamsController::class, 'page'])->name('exams');
        Route::get('/certificates', [CertificateController::class, 'page'])->name('certificates');
    });

    Route::middleware('role:parent')->prefix('parent')->name('parent.')->group(function () {
        Route::get('/dashboard', [ParentDashboardController::class, 'page'])->name('dashboard');
    });
});

// التحقق العلني من الشهادات — لا تحتاج auth (عامة)، Rate Limited.
Route::get('/certificates/verify/{code}', [CertificateController::class, 'verify'])
    ->middleware('throttle:certificate-verification')
    ->name('certificates.verify');

require __DIR__.'/auth.php';
