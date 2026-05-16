<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorDisabledNotification extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Two-Factor Authentication Disabled')
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('Two-factor authentication has been disabled on your account.')
            ->line('Your account is now less secure. We recommend keeping two-factor authentication enabled.')
            ->line('If you did not disable this feature, please secure your account immediately.')
            ->action('View Profile', url('/profile'))
            ->line('Stay safe!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
