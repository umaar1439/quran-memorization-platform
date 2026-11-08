<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ExamResultStatus;
use App\Models\ExamResult;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ينفّذ تصحيح نتيجة امتحان معتمدة عبر Correction Workflow (§5 دورة حياة
 * البيانات — الثبات بعد الاعتماد): لا يُعدَّل السجل الأصلي إلا عبر هذه
 * الآلية المُدقَّقة، مع audit_log كامل بالقيم القديمة والجديدة والسبب.
 */
class CorrectExamResultAction
{
    public function __construct(
        private readonly CreateAuditLogAction $createAuditLog,
    ) {}

    /**
     * @param  array<string, mixed>  $data  بيانات مُصادَق عليها من CorrectExamResultRequest::validated()
     */
    public function execute(ExamResult $result, array $data, User $actor, ?Request $request = null): ExamResult
    {
        if ($result->status !== ExamResultStatus::Approved) {
            throw ValidationException::withMessages([
                'status' => __('messages.exam_results.only_approved'),
            ]);
        }

        if (! $actor->can('exams.approve')) {
            throw ValidationException::withMessages([
                'actor' => __('messages.exam_results.approve_permission'),
            ]);
        }

        return DB::transaction(function () use ($result, $data, $actor, $request) {
            $oldValues = [
                'score' => $result->score,
                'grade' => $result->grade,
            ];

            $result->score = $data['score'];
            $result->grade = $data['grade'] ?? $result->grade;
            $result->save();

            $this->createAuditLog->execute(
                organizationId: $result->organization_id,
                event: 'exam_result.corrected',
                auditableType: ExamResult::class,
                auditableId: $result->id,
                oldValues: $oldValues,
                newValues: [
                    'score' => $result->score,
                    'grade' => $result->grade,
                    'reason' => $data['reason'],
                ],
                user: $actor,
                request: $request,
            );

            return $result;
        });
    }
}
