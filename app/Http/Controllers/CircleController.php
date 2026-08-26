<?php

namespace App\Http\Controllers;

use App\Enums\RecitationRating;
use App\Enums\RecitationType;
use App\Http\Requests\StoreCircleRequest;
use App\Models\Circle;
use App\Models\Mosque;
use App\Models\Recitation;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * صفحات إدارة الحلقات (Inertia). page: قائمة مع فلترة بحسب المسجد.
 * store/update تتحقق (عبر StoreCircleRequest) أن mosque_id/teacher_id
 * ينتميان لمنظمة المستخدم الحالي (IDOR Defense).
 */
class CircleController extends Controller
{
    private const TOTAL_QURAN_AYAHS = 6236;

    public function page(Request $request): Response
    {
        $this->authorize('viewAny', Circle::class);

        $query = Circle::query()->with(['mosque', 'teacher'])->withCount('studentProfiles');

        if ($request->filled('mosque_id')) {
            $query->where('mosque_id', $request->integer('mosque_id'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->string('search') . '%');
        }

        return Inertia::render('Admin/Circles/Index', [
            'circles' => $query->latest()->paginate(20)->withQueryString(),
            'mosques' => Mosque::query()->orderBy('name')->get(['id', 'name']),
            'teachers' => User::query()->role('teacher')->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('search', 'mosque_id'),
        ]);
    }

    /**
     * لوحة تحكم تفصيلية للحلقة (Circle Executive Dashboard) — ملخص عام،
     * بيانات المعلم، قائمة الطلاب، والطالب المتميز — بلا استعلامات N+1.
     * حماية IDOR تتم عبر Global Scope (404) + CirclePolicy::view (403).
     */
    public function show(Circle $circle): Response
    {
        $this->authorize('view', $circle);

        $circle->load([
            'mosque',
            'teacher',
            'studentProfiles' => fn ($query) => $query->with('user')->orderBy('created_at'),
        ]);

        $students = $circle->studentProfiles;

        // كل تسميعات الحلقة في استعلام واحد (مع الأخطاء) لتجنّب N+1.
        $recitations = Recitation::withoutGlobalScopes()
            ->where('circle_id', $circle->id)
            ->with('errors')
            ->orderBy('performed_at')
            ->get();

        $byStudent = $recitations->groupBy('student_id');
        $startOfMonth = now()->startOfMonth();

        $studentsData = $students->map(function (StudentProfile $student) use ($byStudent) {
            $own = $byStudent->get($student->id, collect());

            $memorized = $this->countDistinctMemorizedAyahs($own);
            $count = $own->count();
            $excellent = $own->filter(
                fn (Recitation $recitation) => $recitation->rating?->value === RecitationRating::Excellent->value
            )->count();
            $accuracy = $count > 0 ? round($excellent / $count * 100, 2) : 0.0;

            return [
                'student' => $student,
                'memorized_ayahs' => $memorized,
                'recitation_count' => $count,
                'accuracy' => $accuracy,
                'last_activity_at' => $own->last()?->performed_at,
                'progress_percentage' => round($memorized / self::TOTAL_QURAN_AYAHS * 100, 2),
            ];
        });

        $totalStudents = $students->count();
        $monthRecitations = $recitations->filter(
            fn (Recitation $recitation) => $recitation->performed_at->gte($startOfMonth)
        );
        $recitationsThisMonth = $monthRecitations->count();
        $activeStudents = $monthRecitations->pluck('student_id')->unique()->count();
        $attendanceRate = $totalStudents > 0 ? round($activeStudents / $totalStudents * 100, 2) : 0.0;
        $overallProgress = $totalStudents > 0 ? round($studentsData->avg('progress_percentage'), 2) : 0.0;

        $topStudent = $studentsData
            ->sortByDesc(fn (array $data) => $data['memorized_ayahs'] * 100 + $data['recitation_count'] * 10 + $data['accuracy'])
            ->first();

        return Inertia::render('Admin/Circles/Show', [
            'circle' => $circle,
            'summary' => [
                'total_students' => $totalStudents,
                'recitations_this_month' => $recitationsThisMonth,
                'attendance_rate' => $attendanceRate,
                'overall_progress' => $overallProgress,
            ],
            'students' => $studentsData->values()->all(),
            'topStudent' => $topStudent,
        ]);
    }

    /**
     * يحسب عدد الآيات الفريدة المحفوظة (type=new) من مجموعة تسميعات.
     */
    private function countDistinctMemorizedAyahs($recitations): int
    {
        $covered = [];

        foreach ($recitations as $recitation) {
            if ($recitation->type !== RecitationType::New) {
                continue;
            }

            for ($ayah = $recitation->ayah_from; $ayah <= $recitation->ayah_to; $ayah++) {
                $covered["{$recitation->surah_number}:{$ayah}"] = true;
            }
        }

        return count($covered);
    }

    public function store(StoreCircleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['organization_id'] = $request->user()->organization_id;

        Circle::create($data);

        return back()->with('success', 'تم إضافة الحلقة بنجاح.');
    }

    public function update(StoreCircleRequest $request, Circle $circle): RedirectResponse
    {
        $circle->update($request->validated());

        return back()->with('success', 'تم تحديث بيانات الحلقة بنجاح.');
    }

    public function destroy(Circle $circle): RedirectResponse
    {
        $this->authorize('delete', $circle);

        $circle->delete();

        return back()->with('success', 'تم حذف الحلقة بنجاح.');
    }
}
