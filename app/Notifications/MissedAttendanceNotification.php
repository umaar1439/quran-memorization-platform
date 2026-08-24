<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * إشعار غياب طالب (Phase 13 نقطة 1) — يُرسل عادة لولي الأمر/المعلم عند
 * تسجيل غياب. قناتان: database + mail، مع احترام تفضيلات المستخدم.
 */
class MissedAttendanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $studentId,
        public readonly string $sessionDate,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if (! method_exists($notifiable, 'wantsNotificationChannel') || $notifiable->wantsNotificationChannel('student.missed_attendance', 'database')) {
            $channels[] = 'database';
        }

        if (method_exists($notifiable, 'wantsNotificationChannel') && $notifiable->wantsNotificationChannel('student.missed_attendance', 'mail')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تنبيه غياب')
            ->line("تم تسجيل غياب الطالب بتاريخ {$this->sessionDate}.");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'student_id' => $this->studentId,
            'session_date' => $this->sessionDate,
            'type' => 'missed_attendance',
        ];
    }
}
