<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DownloadCertificateAction;
use App\Actions\GenerateCertificateAction;
use App\Http\Requests\StoreCertificateRequest;
use App\Http\Resources\CertificateResource;
use App\Models\Certificate;
use App\Queries\CertificateDirectoryQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * store: إنشاء شهادة (مع middleware feature:certificates.enabled + Policy).
 * index: قائمة الشهادات ضمن نطاق المستخدم. download: تحميل من private
 * storage (auth + policy). verify: نقطة تحقق عامة بلا auth، بيانات محدودة
 * فقط (لا بيانات شخصية) — §4 تهديد #11، #16.
 */
class CertificateController extends Controller
{
    public function __construct(
        private readonly GenerateCertificateAction $generateCertificate,
        private readonly DownloadCertificateAction $downloadCertificate,
        private readonly CertificateDirectoryQuery $certificates,
    ) {}

    public function store(StoreCertificateRequest $request): JsonResponse
    {
        $certificate = $this->generateCertificate->execute(
            $request->validated(),
            $request->user(),
        );

        return (new CertificateResource($certificate))
            ->additional(['message' => __('messages.certificates.queued')])
            ->response()
            ->setStatusCode(202);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Certificate::class);

        return CertificateResource::collection(
            $this->certificates->paginate($request->user()),
        );
    }

    public function download(Request $request, Certificate $certificate): StreamedResponse
    {
        $this->authorize('view', $certificate);

        return $this->downloadCertificate->execute($certificate);
    }

    /**
     * نقطة تحقق عامة: verification_code → valid/invalid + عنوان + تاريخ
     * فقط. لا بيانات شخصية، لا auth، محمية بـ certificate-verification limiter.
     */
    public function verify(string $code): JsonResponse
    {
        $certificate = $this->certificates->findIssuedByVerificationCode($code);

        if (! $certificate) {
            return response()->json([
                'valid' => false,
            ]);
        }

        return response()->json([
            'valid' => true,
            'certificate_type' => $certificate->certificate_type,
            'issued_at' => $certificate->issued_at,
        ]);
    }

    /**
     * صفحة قائمة شهادات المستخدم الحالي (Inertia) — نفس نطاق index أعلاه.
     */
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', Certificate::class);

        return Inertia::render('Student/Certificates', [
            'certificates' => $this->certificates->paginate($request->user(), withQueryString: true),
        ]);
    }
}
