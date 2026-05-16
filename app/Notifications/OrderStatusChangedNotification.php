<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChangedNotification extends Notification
{
    use Queueable;

    public $order;
    public $oldStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, $oldStatus)
    {
        $this->order = $order;
        $this->oldStatus = $oldStatus;
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
        $message = (new MailMessage)
            ->subject('Order Status Update - Order #' . $this->order->id)
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('Your order status has been updated.')
            ->line('**Order Number:** #' . $this->order->id)
            ->line('**Previous Status:** ' . ucfirst($this->oldStatus))
            ->line('**New Status:** ' . ucfirst($this->order->status));

        // Add status-specific messages
        switch ($this->order->status) {
            case 'processing':
                $message->line('Your order is now being processed.');
                break;
            case 'shipped':
                $message->line('Great news! Your order has been shipped and is on its way.');
                break;
            case 'delivered':
                $message->line('Your order has been delivered. We hope you enjoy your books!');
                break;
            case 'cancelled':
                $message->line('Your order has been cancelled. If you have any questions, please contact support.');
                break;
        }

        return $message
            ->action('View Order', url('/orders'))
            ->line('Thank you for shopping with PageTurner!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->order->status,
        ];
    }
}
