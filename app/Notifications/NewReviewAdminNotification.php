<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReviewAdminNotification extends Notification
{
    use Queueable;

    public $review;

    /**
     * Create a new notification instance.
     */
    public function __construct(Review $review)
    {
        $this->review = $review;
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
            ->subject('New Review Submitted - ' . $this->review->book->title)
            ->greeting('Hello Admin!')
            ->line('A new review has been submitted and may require moderation.')
            ->line('**Review Details:**')
            ->line('Book: ' . $this->review->book->title)
            ->line('Customer: ' . $this->review->user->name)
            ->line('Rating: ' . $this->review->rating . ' / 5 stars')
            ->line('Review: ' . \Str::limit($this->review->comment, 150))
            ->action('View Book Details', url('/book_details/' . $this->review->book->id))
            ->line('Thank you for managing the platform!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'review_id' => $this->review->id,
            'book_id' => $this->review->book_id,
            'book_title' => $this->review->book->title,
            'customer_name' => $this->review->user->name,
            'rating' => $this->review->rating,
        ];
    }
}
