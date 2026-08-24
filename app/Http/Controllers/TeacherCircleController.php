<?php

namespace App\Http\Controllers;

use App\Models\Circle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * صفحة "حلقتي" (Teacher): تعرض الحلقة/الحلقات التي يُدرّسها المعلم الحالي
 * مع قائمة طلابها. لا تحتاج Policy مستقلة — النطاق محدد ضمنياً بـ teacher_id.
 */
class TeacherCircleController extends Controller
{
    public function page(Request $request): Response
    {
        $circles = Circle::query()
            ->with(['mosque', 'studentProfiles.user'])
            ->where('teacher_id', $request->user()->id)
            ->get();

        return Inertia::render('Teacher/MyCircle', [
            'circles' => $circles,
        ]);
    }
}
