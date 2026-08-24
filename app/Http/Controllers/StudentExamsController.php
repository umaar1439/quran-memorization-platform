<?php

namespace App\Http\Controllers;

use App\Models\ExamResult;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * صفحة "أهدافي" (نتائج الاختبارات) للطالب الحالي — تعرض نتائجه المعتمدة
 * وغير المعتمدة فقط لنفسه (النطاق يُحدَّد ضمنياً بـ student_id).
 */
class StudentExamsController extends Controller
{
    public function page(Request $request): Response
    {
        $student = $request->user()->studentProfile;

        abort_if(! $student, 404, 'لا يوجد ملف طالب مرتبط بهذا المستخدم.');

        return Inertia::render('Student/Exams', [
            'results' => ExamResult::query()
                ->with('exam')
                ->where('student_id', $student->id)
                ->latest('created_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }
}
