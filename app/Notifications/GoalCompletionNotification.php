<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * إشعار إكمال هدف (Phase 13 نقطة 1) — يُرسل للطالب/ولي الأمر عند بلوغ
 * achieved_amount >= target_amount لهدف معيَّن. قناتان: database + mail.
 */
class GoalCompletionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $studentId,
        public readonly int $goalId,
        public readonly string $description,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if (! method_exists($notifiable, 'wantsNotificationChannel') || $notifiable->wantsNotificationChannel('goal.completion', 'database')) {
            $channels[] = 'database';
        }

        if (method_exists($notifiable, 'wantsNotificationChannel') && $notifiable->wantsNotificationChannel('goal.completion', 'mail')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('messages.notifications.goal_completed.title'))
            ->line($this->description);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'student_id' => $this->studentId,
            'goal_id' => $this->goalId,
            'description' => $this->description,
            'type' => 'goal_completion',
        ];
    }
}
