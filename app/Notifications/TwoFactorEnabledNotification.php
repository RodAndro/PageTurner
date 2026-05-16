<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorEnabledNotification extends Notification
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
            ->subject('Two-Factor Authentication Enabled')
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('Two-factor authentication has been successfully enabled on your account.')
            ->line('From now on, you will need to enter a verification code sent to your email when logging in.')
            ->line('If you did not enable this feature, please contact support immediately.')
            ->action('View Profile', url('/profile'))
            ->line('Thank you for keeping your account secure!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
