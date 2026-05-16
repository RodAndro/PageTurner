<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Backup Failure Notification
 * 
 * Notifies admin when a scheduled backup fails
 * Sent via email with failure details
 * 
 * @package App\Notifications
 */
class BackupFailureNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Error message for the failure
     */
    protected string $errorMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $errorMessage = 'Backup failed')
    {
        $this->errorMessage = $errorMessage;
    }

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
            ->subject('Backup Failed')
            ->line('A scheduled backup has failed.')
            ->line('Error: ' . $this->errorMessage)
            ->action('Check Backups', url('/admin/backups'));
    }
}
