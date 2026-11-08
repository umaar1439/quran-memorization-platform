<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * الحقول القابلة للتعبئة الجماعية. عمداً بلا organization_id لمنع
     * Privilege Escalation عبر تعديل حمولة الطلب (انظر §4 نموذج التهديدات).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'locale',
        'notification_preferences',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'last_login_at' => 'datetime',
            'notification_preferences' => 'array',
        ];
    }

    /**
     * يتحقق إن كان المستخدم يسمح بقناة إشعار معينة لنوع إشعار معين.
     * الافتراضي (بلا تفضيل محفوظ) هو "مفعّل" لكل القنوات — Opt-out لا Opt-in
     * (Phase 13 نقطة 2: يحترم تفضيلات المستخدم إن وُجدت، وإلا السلوك الافتراضي).
     */
    public function wantsNotificationChannel(string $notificationKey, string $channel): bool
    {
        $preferences = $this->notification_preferences ?? [];

        if (! array_key_exists($notificationKey, $preferences)) {
            return true;
        }

        $channels = $preferences[$notificationKey];

        if (! is_array($channels)) {
            return true;
        }

        return (bool) ($channels[$channel] ?? true);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    public function guardian(): HasOne
    {
        return $this->hasOne(Guardian::class);
    }

    public function circlesTaught(): HasMany
    {
        return $this->hasMany(Circle::class, 'teacher_id');
    }

    public function teacherAttendances(): HasMany
    {
        return $this->hasMany(TeacherAttendance::class, 'teacher_id');
    }

    /**
     * يحدد اسم مسار لوحة التحكم المنزلية حسب دور المستخدم
     * (لإعادة التوجيه بعد تسجيل الدخول/التسجيل).
     *
     * يستخدم استعلاماً مباشراً غير مقيد بسياق الفريق (team) لأن دور
     * super-admin قد يكون team-scoped في بيئة الاختبار بينما organization_id
     * لديه null — فيكون الاعتماد على hasRole() (المقيد بالفريق) غير موثوق هنا.
     */
    public function homeRouteName(): string
    {
        $roleNames = Role::withoutGlobalScopes()
            ->whereIn('id', DB::table('model_has_roles')
                ->where('model_type', self::class)
                ->where('model_id', $this->id)
                ->pluck('role_id'))
            ->pluck('name')
            ->all();

        if (array_intersect($roleNames, ['super-admin', 'org-admin', 'supervisor'])) {
            return 'admin.dashboard';
        }

        if (in_array('teacher', $roleNames, true)) {
            return 'teacher.dashboard';
        }

        if (in_array('student', $roleNames, true)) {
            return 'student.dashboard';
        }

        if (in_array('parent', $roleNames, true)) {
            return 'parent.dashboard';
        }

        return 'dashboard';
    }
}
