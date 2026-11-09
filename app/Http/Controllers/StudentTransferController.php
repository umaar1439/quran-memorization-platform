<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\TransferStudentAction;
use App\Http\Requests\TransferStudentRequest;
use App\Http\Resources\StudentTransferResource;
use App\Models\StudentTransfer;
use App\Models\User;
use App\Queries\MosqueOptionsQuery;
use App\Queries\StudentTransferQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
        private readonly StudentTransferQuery $transfers,
        private readonly MosqueOptionsQuery $mosqueOptions,
    ) {}

    public function store(TransferStudentRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $transfer = $this->transferStudent->execute(
            $request->toData(),
            $actor,
            $request,
        );

        return (new StudentTransferResource($transfer))->response()->setStatusCode(201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', StudentTransfer::class);

        return StudentTransferResource::collection(
            $this->transfers->paginate(
                $request->filled('student_id') ? $request->integer('student_id') : null,
            ),
        );
    }

    /**
     * صفحة سجل نقل الطلاب مع نموذج نقل جديد (Inertia).
     */
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', StudentTransfer::class);

        return Inertia::render('Admin/StudentTransfers/Index', [
            'transfers' => $this->transfers->paginate(
                $request->filled('student_id') ? $request->integer('student_id') : null,
                withQueryString: true,
            ),
            'mosques' => $this->mosqueOptions->all(),
            'students' => $this->transfers->studentOptions(),
        ]);
    }
}
