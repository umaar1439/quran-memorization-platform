<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Queries\TeacherCircleQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * صفحة "حلقتي" (Teacher): تعرض الحلقة/الحلقات التي يُدرّسها المعلم الحالي
 * مع قائمة طلابها. لا تحتاج Policy مستقلة — النطاق محدد ضمنياً بـ teacher_id.
 */
class TeacherCircleController extends Controller
{
    public function __construct(
        private readonly TeacherCircleQuery $circles,
    ) {}

    public function page(Request $request): Response
    {
        return Inertia::render('Teacher/MyCircle', [
            'circles' => $this->circles->forTeacher($request->user()),
        ]);
    }
}
