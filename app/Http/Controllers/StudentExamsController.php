<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Queries\StudentExamResultsQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * صفحة "أهدافي" (نتائج الاختبارات) للطالب الحالي — تعرض نتائجه المعتمدة
 * وغير المعتمدة فقط لنفسه (النطاق يُحدَّد ضمنياً بـ student_id).
 */
class StudentExamsController extends Controller
{
    public function __construct(
        private readonly StudentExamResultsQuery $results,
    ) {}

    public function page(Request $request): Response
    {
        $student = $request->user()->studentProfile;

        abort_if(! $student, 404, __('messages.students.missing_profile'));

        return Inertia::render('Student/Exams', [
            'results' => $this->results->paginate($student->id),
        ]);
    }
}
