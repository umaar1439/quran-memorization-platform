<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * إشعار إنجاز طالب عند إكمال هدف أو سورة كاملة (Phase 13 نقطة 1).
 * قناتان: database + mail، مع احترام تفضيلات المستخدم (User::wantsNotificationChannel).
 * البنية قابلة لإضافة SMS/WhatsApp لاحقاً عبر توسعة via() فقط.
 */
class StudentAchievementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $studentId,
        public readonly string $achievementType,
        public readonly string $description,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if (! method_exists($notifiable, 'wantsNotificationChannel') || $notifiable->wantsNotificationChannel('student.achievement', 'database')) {
            $channels[] = 'database';
        }

        if (method_exists($notifiable, 'wantsNotificationChannel') && $notifiable->wantsNotificationChannel('student.achievement', 'mail')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('إنجاز جديد')
            ->line($this->description);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'student_id' => $this->studentId,
            'achievement_type' => $this->achievementType,
            'description' => $this->description,
            'type' => 'student_achievement',
        ];
    }
}
