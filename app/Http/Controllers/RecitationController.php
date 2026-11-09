<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RecordRecitationAction;
use App\Http\Requests\StoreRecitationRequest;
use App\Http\Resources\RecitationResource;
use App\Models\Recitation;
use App\Queries\QuickRecitationQuery;
use App\Queries\RecitationDirectoryQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * store: تسجيل تسميع (Teacher فقط، عبر StoreRecitationRequest + Policy + Action).
 * index: تسميعات الحلقة (Teacher) أو تسميعات الطالب (Student/Parent).
 * show: تفاصيل تسميع واحد مع أخطائه.
 */
class RecitationController extends Controller
{
    public function __construct(
        private readonly RecordRecitationAction $recordRecitation,
        private readonly RecitationDirectoryQuery $recitations,
        private readonly QuickRecitationQuery $quickRecitation,
    ) {}

    public function store(StoreRecitationRequest $request): JsonResponse
    {
        $recitation = $this->recordRecitation->execute(
            $request->toData(),
            $request->user(),
        );

        return (new RecitationResource($recitation->load('errors')))
            ->response()
            ->setStatusCode(201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Recitation::class);

        return RecitationResource::collection($this->recitations->paginate($request->user()));
    }

    public function show(Recitation $recitation): JsonResponse
    {
        $this->authorize('view', $recitation);

        return (new RecitationResource($recitation->load('errors')))->response();
    }

    /**
     * صفحة "تسميع سريع" (Teacher) — نموذج تسجيل تسميع جديد + آخر تسميعات
     * المعلم لطلاب حلقته.
     */
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', Recitation::class);

        return Inertia::render('Teacher/QuickRecitation', [
            'students' => $this->quickRecitation->students($request->user()),
            'recentRecitations' => $this->quickRecitation->recent($request->user()),
        ]);
    }
}
