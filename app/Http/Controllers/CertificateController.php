<?php

namespace App\Http\Controllers;

use App\Actions\GenerateCertificateAction;
use App\Enums\CertificateStatus;
use App\Http\Requests\StoreCertificateRequest;
use App\Models\Certificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
    ) {}

    public function store(StoreCertificateRequest $request): JsonResponse
    {
        $certificate = $this->generateCertificate->execute(
            $request->validated(),
            $request->user(),
        );

        return response()->json([
            'data' => $certificate,
            'message' => 'جاري توليد الشهادة في الخلفية، سيتم إعلامك فور جاهزيتها.',
        ], 202);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Certificate::class);

        $user = $request->user();

        $query = Certificate::query();

        if ($user->hasRole('student')) {
            $studentProfile = $user->studentProfile;
            $query->where('student_id', $studentProfile?->id);
        } elseif ($user->hasRole('parent')) {
            $guardian = $user->guardian;
            $studentIds = $guardian
                ? $guardian->students()->pluck('student_profiles.id')
                : collect();
            $query->whereIn('student_id', $studentIds);
        }

        return response()->json([
            'data' => $query->latest('issued_at')->paginate(20),
        ]);
    }

    public function download(Request $request, Certificate $certificate): StreamedResponse
    {
        $this->authorize('view', $certificate);

        return Storage::disk('local')->response(
            $certificate->getRawOriginal('file_path'),
        );
    }

    /**
     * نقطة تحقق عامة: verification_code → valid/invalid + عنوان + تاريخ
     * فقط. لا بيانات شخصية، لا auth، محمية بـ certificate-verification limiter.
     */
    public function verify(string $code): JsonResponse
    {
        $certificate = Certificate::withoutGlobalScopes()
            ->where('verification_code', $code)
            ->first();

        if (! $certificate || $certificate->status !== CertificateStatus::Issued) {
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

        $user = $request->user();

        $query = Certificate::query();

        if ($user->hasRole('student')) {
            $studentProfile = $user->studentProfile;
            $query->where('student_id', $studentProfile?->id);
        } elseif ($user->hasRole('parent')) {
            $guardian = $user->guardian;
            $studentIds = $guardian
                ? $guardian->students()->pluck('student_profiles.id')
                : collect();
            $query->whereIn('student_id', $studentIds);
        }

        return Inertia::render('Student/Certificates', [
            'certificates' => $query->latest('issued_at')->paginate(20)->withQueryString(),
        ]);
    }
}
