<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * إشعار نتيجة امتحان (Phase 13 نقطة 1) — يُرسل عند اعتماد نتيجة
 * (ExamResultController::approve). قناتان: database + mail.
 */
class ExamResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $studentId,
        public readonly int $examResultId,
        public readonly string $grade,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if (! method_exists($notifiable, 'wantsNotificationChannel') || $notifiable->wantsNotificationChannel('exam.result', 'database')) {
            $channels[] = 'database';
        }

        if (method_exists($notifiable, 'wantsNotificationChannel') && $notifiable->wantsNotificationChannel('exam.result', 'mail')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('messages.notifications.exam_result.title'))
            ->line(__('messages.notifications.exam_result.body', ['grade' => $this->grade]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'student_id' => $this->studentId,
            'exam_result_id' => $this->examResultId,
            'grade' => $this->grade,
            'type' => 'exam_result',
        ];
    }
}
