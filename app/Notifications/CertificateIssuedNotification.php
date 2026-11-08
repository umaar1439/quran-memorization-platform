<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * إشعار إصدار شهادة (Phase 13 نقطة 1) — يُرسل عند إصدار شهادة
 * (GenerateCertificateAction). قناتان: database + mail.
 */
class CertificateIssuedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $certificateId,
        public readonly string $certificateType,
        public readonly string $verificationCode,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if (! method_exists($notifiable, 'wantsNotificationChannel') || $notifiable->wantsNotificationChannel('certificate.issued', 'database')) {
            $channels[] = 'database';
        }

        if (method_exists($notifiable, 'wantsNotificationChannel') && $notifiable->wantsNotificationChannel('certificate.issued', 'mail')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('messages.notifications.certificate_issued.title'))
            ->line(__('messages.notifications.certificate_issued.body', ['type' => $this->certificateType]))
            ->line(__('messages.notifications.certificate_issued.verification_code', ['code' => $this->verificationCode]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'certificate_id' => $this->certificateId,
            'certificate_type' => $this->certificateType,
            'verification_code' => $this->verificationCode,
            'type' => 'certificate_issued',
        ];
    }
}
