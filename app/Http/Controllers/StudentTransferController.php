<?php

namespace App\Http\Controllers;

use App\Actions\TransferStudentAction;
use App\Http\Requests\TransferStudentRequest;
use App\Models\Mosque;
use App\Models\StudentProfile;
use App\Models\StudentTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * store: نقل طالب (Supervisor+، عبر TransferStudentRequest + Policy + Action).
 * index: سجل النقل للطالب (نطاق المستخدم يُحدَّد عبر Policy/Global Scope).
 */
class StudentTransferController extends Controller
{
    public function __construct(
        private readonly TransferStudentAction $transferStudent,
    ) {}

    public function store(TransferStudentRequest $request): JsonResponse
    {
        $transfer = $this->transferStudent->execute(
            $request->validated(),
            $request->user(),
            $request,
        );

        return response()->json([
            'data' => $transfer,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StudentTransfer::class);

        $query = StudentTransfer::query();

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->integer('student_id'));
        }

        return response()->json([
            'data' => $query->latest('transferred_at')->paginate(20),
        ]);
    }

    /**
     * صفحة سجل نقل الطلاب مع نموذج نقل جديد (Inertia).
     */
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', StudentTransfer::class);

        $query = StudentTransfer::query()->with(['student.user', 'fromMosque', 'toMosque']);

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->integer('student_id'));
        }

        return Inertia::render('Admin/StudentTransfers/Index', [
            'transfers' => $query->latest('transferred_at')->paginate(20)->withQueryString(),
            'mosques' => Mosque::query()->orderBy('name')->get(['id', 'name']),
            'students' => StudentProfile::query()->with('user')->get(['id', 'user_id', 'mosque_id', 'circle_id']),
        ]);
    }
}
