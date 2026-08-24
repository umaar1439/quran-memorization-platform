<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * إشعار توصية بامتحان عند إكمال طالب لسورة/جزء (من recitations) — Phase 9
 * §3: "لا إنشاء تلقائي"، فقط توصية/علم يُرسل للمعلم/المشرف. يُرسل عبر
 * Queue دائماً (§1 Notifications).
 */
class ExamRecommendedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $studentId,
        public readonly string $reason,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'student_id' => $this->studentId,
            'reason' => $this->reason,
            'type' => 'exam_recommendation',
        ];
    }
}
